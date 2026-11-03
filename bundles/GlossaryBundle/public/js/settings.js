/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.glossary.settings");
/**
 * @private
 */
pimcore.bundle.glossary.settings = Class.create({

    initialize: function () {
        // a copy with an empty entry for "all languages"; the global list is shared by all language selectors
        this.languages = [''].concat(pimcore.settings.websiteLanguages);
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_glossary");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_glossary",
                iconCls: "pimcore_icon_glossary",
                title: t("glossary"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_glossary");


            this.panel.on("destroy", function () {
                Ext.destroy(this.rowDropZone, this.editorDropZone);
                pimcore.globalmanager.remove("bundle_glossary");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
        this.store = pimcore.helpers.grid.buildDefaultStore(
            Routing.generate('pimcore_bundle_glossary_settings_glossary'),
            [
                'id', {name: 'text', allowBlank: false}, 'language', 'casesensitive', 'exactmatch',
                'site', 'link', 'abbr', 'creationDate', 'modificationDate'
            ],
            itemsPerPage
        );

        // show save errors (e.g. a rejected link scheme) instead of silently keeping the row dirty
        this.store.getProxy().on('exception', function (proxy, response) {
            var data = Ext.decode(response.responseText, true);
            pimcore.helpers.showNotification(t('error'), data && data.message ? data.message : t('error'), 'error');
        });

        this.filterField = Ext.create("Ext.form.TextField", {
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

        var casesensitiveCheck = new Ext.grid.column.Check({
            text: t("casesensitive"),
            dataIndex: "casesensitive",
            flex: 55,
            editor: {
                xtype: 'checkbox',
            }
        });

        var exactmatchCheck = new Ext.grid.column.Check({
            text: t("exactmatch"),
            dataIndex: "exactmatch",
            flex: 50,
            editor: {
                xtype: 'checkbox',
            }
        });

        var typesColumns = [
            {text: t("text"), flex: 200, sortable: true, dataIndex: 'text', editor: new Ext.form.TextField({})},
            {text: t("link"), flex: 200, sortable: true, dataIndex: 'link',
                editor: {
                    xtype: 'textfield',
                    id: 'linkEditor',
                    fieldCls: "input_drop_target",
                },
                tdCls: "pimcore_droptarget_input"
            },
            {text: t("abbr"), flex: 200, sortable: true, dataIndex: 'abbr', editor: new Ext.form.TextField({})},
            {text: t("language"), flex: 50, sortable: true, dataIndex: 'language', editor: new Ext.form.ComboBox({
                    store: this.languages,
                    mode: "local",
                    editable: false,
                    triggerAction: "all"
                })},
            casesensitiveCheck,
            exactmatchCheck,
            {text: t("site"), flex: 200, sortable:true, dataIndex: "site", editor: new Ext.form.ComboBox({
                    store: pimcore.globalmanager.get("sites"),
                    valueField: "id",
                    displayField: "domain",
                    editable: false,
                    triggerAction: "all"
                }), renderer: function (siteId) {
                    var store = pimcore.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if(pos >= 0) {
                        return store.getAt(pos).get("domain");
                    }
                }},
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        let data = grid.getStore().getAt(rowIndex);
                        pimcore.helpers.deleteConfirm(t('glossary'), data.data.id, function () {
                            grid.getStore().removeAt(rowIndex);
                        }.bind(this));

                    }.bind(this)
                }]
            }
        ];

        this.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 1,
            clicksToMoveEditor: 1,
            listeners: {
                // the editor floats above the grid rows; being the topmost target, it gets the drop while editing
                beforeedit: function (plugin) {
                    if (!this.editorDropZone) {
                        this.editorDropZone = this.createLinkDropZone(plugin.editor.body, function (path) {
                            Ext.getCmp('linkEditor').setValue(path);
                        });
                    }
                }.bind(this),
                delay: 1
            }
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "pimcore_icon_add"
                },"->",{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            autoScroll: true,
            store: this.store,
            columns: {
                items: typesColumns,
                defaults: {
                    renderer: Ext.util.Format.htmlEncode
                },
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.rowEditing
            ],

            trackMouseOver: true,
            columnLines: true,
            bbar: this.pagingtoolbar,
            bodyCls: "pimcore_editable_grid",
            stripeRows: true,
            tbar: toolbar,
            viewConfig: {
                forceFit: true
            }
        });

        // one drop zone for all rows, the row is resolved from the drop event
        this.grid.on("viewready", function (grid) {
            var view = grid.getView();
            this.rowDropZone = this.createLinkDropZone(view.getEl(), function (path, rowEl) {
                view.getRecord(rowEl).set("link", path);
            }, function (e) {
                return e.getTarget(view.getItemSelector(), view.getEl());
            });
        }.bind(this));

        this.store.load();

        return this.grid;
    },

    /**
     * @param {Ext.dom.Element} el
     * @param {Function} setLink called with the path of the dropped element and the target
     * @param {Function} [getTarget] resolves the target from the event, the whole element by default
     */
    createLinkDropZone: function (el, setLink, getTarget) {
        // only documents are stored as internal link; the path of an asset or data object would be stored and
        // rendered as relative external link
        var isDocument = function (data) {
            return data.records.length === 1 && data.records[0].data.elementType === "document";
        };

        return new Ext.dd.DropZone(el, {
            ddGroup: "element",

            getTargetFromEvent: getTarget || function () {
                return this.getEl();
            },

            onNodeOver: function (target, dd, e, data) {
                if (isDocument(data)) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            },

            onNodeDrop: function (target, dd, e, data) {
                if (!isDocument(data) || !pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                    return false;
                }

                setLink(data.records[0].data.path, target);

                return true;
            }
        });
    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0,{
            name: t('/')
        });
    }
});
