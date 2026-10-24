# TODO: Selectable return type for `calculatedValue`

Scope: `models/DataObject/ClassDefinition/Data/CalculatedValue.php`,
`models/DataObject/ClassDefinition/CalculatorClassInterface.php`, the calculated-value helpers in
`models/DataObject/Service.php` (`evaluateExpression()`, `getCalculatedFieldValue()`,
`getCalculatedFieldValueForEditMode()`, `:1440-1570`), the filter SQL in `models/DataObject/ClassDefinition/Data.php:411`,
classification store keys (`models/DataObject/Classificationstore.php:340`) and `tests/Support/Helper/DataType/Calculator.php`.
The class editor UI lives outside this repo (`pimcore/admin-ui-classic-bundle`, Studio).

Goal: choose the return type when the field is defined in the class (`string`, `int`, `float`, `bool`, `array`, `mixed`) instead
of everything ending up as a string. Default `mixed` → existing classes and calculators are unaffected.

Estimate: core ≈ 1.5–2 days incl. tests, UI bundle ≈ 0.5–1 day.

## Current state

- The generated getter is already untyped: `getReturnTypeDeclaration()` / `getPhpdocReturnType()` return `mixed`.
- The expression calculator already returns `mixed` (arrays pass through `evaluateExpression()`).
- The string restriction comes from:

| Where | What forces string |
|---|---|
| `CalculatorClassInterface.php:25` | `compute(): string` |
| `CalculatedValue.php:146` `getDataForQueryResource()` | `(string)$data` into `varchar(columnLength)` → an array is stored as `"Array"` |
| `getForCsvExport()`, `getVersionPreview()`, `getDataForEditmode()` | `(string)` casts |
| `ClassDefinition/Data.php:411` | filter SQL branches on `elementType` (date / numeric) only |

- `elementType` (`input`, `numeric`, `boolean`, `date`, …) is a display type that partly overlaps with the wanted return type.

---

## [ ] CV-01 – Widen `CalculatorClassInterface::compute()` to `mixed`

**File:** `models/DataObject/ClassDefinition/CalculatorClassInterface.php:25`

- `compute(Concrete $object, CalculatedValue $context): string` → `: mixed`.
- BC-safe: return types are covariant, existing implementations declaring `: string` stay valid.
- Keep `getCalculatedValueForEditMode(): string` – it is the display representation.

---

## [ ] CV-02 – Add the `returnType` property to the field definition

**File:** `models/DataObject/ClassDefinition/Data/CalculatedValue.php`

- New `public string $returnType = 'mixed'` + getter / setter, validated against an allowed list (`string`, `int`, `float`,
  `bool`, `array`, `mixed`).
- `getReturnTypeDeclaration()` / `getPhpdocReturnType()` derive from it; nullable for every non-`mixed` type (`?array`),
  because `Service::getCalculatedFieldValue()` returns `null` when the calculator cannot be resolved.
- Decide the relation to `elementType`: derive one from the other, or validate consistency on class save
  (e.g. `returnType = array` with `elementType = numeric` is rejected).

---

## [ ] CV-03 – Coerce / validate the computed value against `returnType`

**File:** `models/DataObject/Service.php::getCalculatedFieldValue()` (`:1509`)

- A calculator returning the wrong type would throw a `TypeError` in the typed getter. Cast scalars, reject (log + `null`
  or exception – decide) non-castable values, e.g. a string for `array`.
- Expression errors currently return the `SyntaxError` message as the value – incompatible with a non-string
  `returnType`; return `null` + log in that case.

---

## [ ] CV-04 – Query column type and serialization per `returnType`

**File:** `CalculatedValue.php::getQueryColumnType()`, `getDataForQueryResource()` (`:146`)

- Column type from `returnType`: `int` → `bigint`, `float` → `double`, `bool` → `tinyint(1)`, `array` → `json` (or
  `longtext`), `string` / `mixed` → `varchar(columnLength)` as today.
- `getDataForQueryResource()`: `json_encode()` arrays, bool → `0|1`, no blind `(string)` cast.
- Changing `returnType` on an existing field triggers an ALTER on the query table – check that the class-save path
  handles the type change (and existing varchar data) cleanly.

---

## [ ] CV-05 – Export, version preview, edit mode and grid for non-scalar values

**File:** `CalculatedValue.php::getForCsvExport()`, `getVersionPreview()`, `getDataForEditmode()`, `getDataForGrid()`

- Arrays: JSON (or a readable implode) instead of `(string)` → `"Array"` + warning.
- Bool: consistent `0|1` / `true|false` representation.
- `isEqual()` uses `===` – fine for arrays, but check the dirty / version comparison paths with arrays.

---

## [ ] CV-06 – Filtering per `returnType`

**File:** `models/DataObject/ClassDefinition/Data.php:411`, `CalculatedValue::isFilterable()`

- Numeric / bool filters should follow `returnType`, not only `elementType`.
- `array` → `isFilterable()` false, or JSON-based filtering (`JSON_CONTAINS`) – decide.

---

## [ ] CV-07 – Classification store calculated-value keys

**File:** `models/DataObject/Classificationstore.php:340`

- Keys of type `calculatedValue` go through the same `Service::getCalculatedFieldValue()` path – make sure `returnType`
  is read from the key definition and coercion (CV-03) applies.

---

## [ ] CV-08 – Tests

**File:** `tests/Support/Helper/DataType/Calculator.php`, data type tests for `calculatedValue`

- Calculator returning `array` / `int` / `bool`, generated getter signature per `returnType`, query column content,
  CSV export, BC: class without `returnType` behaves exactly as before.

---

## [ ] CV-09 – Class editor UI (outside this repo)

**Repo:** `pimcore/admin-ui-classic-bundle` (and Studio if used)

- Return type combo in the `calculatedValue` field settings, default `mixed`.
- Edit mode / grid rendering for arrays.
