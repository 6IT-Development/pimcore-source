<?php

declare(strict_types=1);

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

namespace Pimcore\Tests\Unit\Document\Glossary;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Bundle\GlossaryBundle\Tool\Processor;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\TestCase;

class GlossaryTest extends TestCase
{
    protected Processor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $pimcoreModule = $this->getModule('\\' . Pimcore::class);
        $this->processor = $pimcoreModule->grabService(Processor::class);
    }

    public function testGlossary(): void
    {
        $entry = new Glossary();
        $entry->setText('Glossary');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<body><p>This is a Test for the Glossary</p></body>', [], 'en', null, null);

        $expect = '<body><p>This is a Test for the <a class="pimcore_glossary" href="/test">Glossary</a></p></body>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryWithHtmlEntities(): void
    {
        $entry = new Glossary();
        $entry->setText('Entity');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<body><p>This is a Test for the&nbsp;Entity &copy;</p></body>',
            [],
            'en',
            null,
            null
        );

        $expect = '<body><p>This is a Test for the&nbsp;<a class="pimcore_glossary" href="/test">Entity</a> &copy;</p></body>';

        $this->assertSame(html_entity_decode($expect), $result);
    }

    public function testGlossaryWithHtmlEntities2(): void
    {
        $entry = new Glossary();
        $entry->setText('Eintrag');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<body><p>Test &nbsp; Eintrag ©</p></body>', [], 'en', null, null);

        $expect = '<body><p>Test &nbsp; <a class="pimcore_glossary" href="/test">Eintrag</a> &copy;</p></body>';

        $this->assertSame(html_entity_decode($expect), $result);
    }

    public function testGlossaryWithHtml(): void
    {
        $entry = new Glossary();
        $entry->setText('HTML');
        $entry->setLink('/test');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<section class="c-content" id="c-20-content-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="text-content">
                        <div class="text-content__pre h6 text-center">Über uns</div>
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; HTML &copy;</p>
                    </div>
                </div>
            </div>
        </div>
    </section>', [],
            'en',
            null,
            null
        );

        $expect = '<section class="c-content" id="c-20-content-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="text-content">
                        <div class="text-content__pre h6 text-center">Über uns</div>
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; <a class="pimcore_glossary" href="/test">HTML</a> ©</p>
                    </div>
                </div>
            </div>
        </div>
    </section>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryWithAnotherHtml(): void
    {
        $entry = new Glossary();
        $entry->setText('hans');
        $entry->setLink('/hans');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<p>hans &amp; gretl</p>', [],
            'en',
            null,
            null
        );

        $expect = '<p><a class="pimcore_glossary" href="/hans">hans</a> &amp; gretl</p>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryWithLowerThenAndGreaterThenHtml(): void
    {
        $entry = new Glossary();
        $entry->setText('huber');
        $entry->setLink('/huber');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<p>Huber &lt;&gt; is the best</p>', [],
            'en',
            null,
            null
        );

        $expect = '<p><a class="pimcore_glossary" href="/huber">huber</a> &lt;&gt; is the best</p>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryKeepsEscapedContentEscaped(): void
    {
        $entry = new Glossary();
        $entry->setText('here');
        $entry->setLink('/here');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<div><p>Code: &lt;div&gt; &amp;amp;</p><p>Use &lt;script&gt;alert(1)&lt;/script&gt; here</p></div>', [],
            'en',
            null,
            null
        );

        $expect = '<div><p>Code: &lt;div&gt; &amp;amp;</p>'
            . '<p>Use &lt;script&gt;alert(1)&lt;/script&gt; <a class="pimcore_glossary" href="/here">here</a></p></div>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryOnlyReplacesText(): void
    {
        foreach ([['span', '/span', null], ['Hypertext', '/hypertext', null], ['PHP', null, 'Hypertext Preprocessor']] as [$text, $link, $abbr]) {
            $entry = new Glossary();
            $entry->setText($text);
            if ($link) {
                $entry->setLink($link);
            }
            if ($abbr) {
                $entry->setAbbr($abbr);
            }
            $entry->setLanguage('en');
            $entry->save();
        }

        $result = $this->processor->parse(
            '<p>A <span class="span" title="span">time span</span>, PHP and Hypertext</p>', [],
            'en',
            null,
            null
        );

        $expect = '<p>A <span class="span" title="span">time <a class="pimcore_glossary" href="/span">span</a></span>, '
            . '<abbr class="pimcore_glossary" title="Hypertext Preprocessor">PHP</abbr> and '
            . '<a class="pimcore_glossary" href="/hypertext">Hypertext</a></p>';

        $this->assertSame($expect, $result);
    }

    public function testGlossarySkipsDescendantsOfBlockedTags(): void
    {
        $entry = new Glossary();
        $entry->setText('Blocked');
        $entry->setLink('/blocked');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse(
            '<div>Blocked <h2><strong>Blocked</strong></h2><script>var Blocked = 1;</script>'
            . '<pre><code><span>Blocked</span></code></pre></div>', [],
            'en',
            null,
            null
        );

        $expect = '<div><a class="pimcore_glossary" href="/blocked">Blocked</a> <h2><strong>Blocked</strong></h2>'
            . '<script>var Blocked = 1;</script><pre><code><span>Blocked</span></code></pre></div>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryEscapesAbbreviationAndLink(): void
    {
        $entry = new Glossary();
        $entry->setText('Escaped');
        $entry->setAbbr('5" display');
        $entry->setLink('https://example.com/?a=1&b=2');
        $entry->setLanguage('en');
        $entry->save();

        $result = $this->processor->parse('<p>Escaped</p>', [], 'en', null, null);

        $expect = '<p><a class="pimcore_glossary" href="https://example.com/?a=1&amp;b=2">'
            . '<abbr class="pimcore_glossary" title=\'5" display\'>Escaped</abbr></a></p>';

        $this->assertSame($expect, $result);
    }

    public function testGlossaryRejectsScriptLinks(): void
    {
        $this->assertTrue(Glossary::isAllowedLink('/relative/path'));
        $this->assertTrue(Glossary::isAllowedLink('mailto:info@example.com'));
        $this->assertFalse(Glossary::isAllowedLink(" java\tscript:alert(1)"));
        $this->assertFalse(Glossary::isAllowedLink('data:text/html,x'));

        $entry = new Glossary();
        $entry->setText('Script');
        $entry->setLink('javascript:alert(document.cookie)');
        $entry->setLanguage('en');

        $this->expectException(\InvalidArgumentException::class);
        $entry->save();
    }

    public function testGlossaryAcceptsNullValuesOnUpdate(): void
    {
        $entry = new Glossary();
        $entry->setText('Nullable');
        $entry->setLink('/nullable');
        $entry->setLanguage('en');
        $entry->save();

        // the admin grid sends all fields back, including the NULL abbreviation of a link-only entry
        $reloaded = Glossary::getById($entry->getId());
        $values = $reloaded->getObjectVars();
        $values['language'] = 'de';
        $reloaded->setValues($values);
        $reloaded->save();

        $saved = Glossary::getById($entry->getId());
        $this->assertNull($saved->getAbbr());
        $this->assertSame('/nullable', $saved->getLink());
        $this->assertSame('de', $saved->getLanguage());

        $saved->setValues(['link' => null, 'abbr' => 'Nullable abbreviation']);
        $saved->save();

        $saved = Glossary::getById($entry->getId());
        $this->assertNull($saved->getLink());
        $this->assertSame('Nullable abbreviation', $saved->getAbbr());
    }
}
