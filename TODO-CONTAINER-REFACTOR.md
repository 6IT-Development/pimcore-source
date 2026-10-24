# TODO: Replace `Pimcore::getContainer()` in models

Scope: `models/**`, `lib/Model/AbstractModel.php`, generated class code (`models/DataObject/ClassDefinition/Data/**`
`getGetterCode*()`), with `lib/**` and `bundles/**` as follow-up.

`Pimcore::getContainer()` (`lib/Pimcore.php:119`) is only `@deprecated` in the docblock (no `trigger_deprecation()`):
Pimcore itself, generated classes and third-party bundles depend on it. It stays for BC – the goal is to stop **models**
from using it, not to remove it.

## Current state

- **114** calls in **62** files under `models/`, **49** / 25 in `lib/`, **19** / 12 in `bundles/`.
- **41** `Pimcore::getEventDispatcher()` calls in `models/` – also a container lookup (`lib/Pimcore.php:87`).
- Most fetched services in `models/`:

| Count | Service |
|---|---|
| 12 | `messenger.bus.pimcore-core` |
| 11 | `pimcore.model.factory` |
| 9 | `pimcore.implementation_loader.asset.metadata.data` |
| 9 | `LocaleServiceInterface::class` |
| 5 | `translator` |
| 5 | `LockFactory::class` |
| 4 | `pimcore.class.resolver.asset`, `MarshallerService::class` |
| 3 | `request_stack` |

Why it is deeply involved:
- Active record: models are created with `new`, loaded via static `::getById()`, restored by `unserialize()` from cache –
  there is no constructor the container controls, so no injection point.
- `AbstractModel::getModelFactory()` (`lib/Model/AbstractModel.php:237`) is itself a static container lookup.
- Generated code emits `\Pimcore::getContainer()` (e.g. `CalculatedValue::getGetterCodeLocalizedfields()`,
  `CalculatedValue.php:241`) → every project's generated classes depend on it.
- Every service fetched this way must be `public: true`.

Related entries already in `models/TODO-3-IMPROVEMENTS.md`: Editables (~`:1117`), `Localizedfield` (~`:1150`),
`Translation` sanitizer (~`:822`), Version storage adapter (~`:1502`), implementation loader helpers (~`:1244`).

Approach: **typed service locator** (DI-01 … DI-05, ≈ 2–4 days, mostly mechanical), then move behaviour out of models
**per domain, opportunistically** (DI-06). Full constructor DI in models is not feasible without dropping active record.

---

## [ ] DI-01 – Introduce a typed model service locator

**New:** e.g. `lib/Model/ModelServices.php` + a Symfony `ServiceLocator` (`!service_locator` / `ServiceSubscriberInterface`)

- Static facade with typed accessors (`factory(): Factory`, `messageBus(): MessageBusInterface`,
  `eventDispatcher(): EventDispatcherInterface`, `locale(): LocaleServiceInterface`, `lockFactory(): LockFactory`, …).
- Locator set once on kernel boot (bundle `boot()`), contains only the services models need → those can become private.
- Test seam: allow replacing the locator in unit tests (reset between tests).
- Clear exception when accessed before boot (instead of a null container fatal).

---

## [ ] DI-02 – `AbstractModel::getModelFactory()` and the messenger bus

**File:** `lib/Model/AbstractModel.php:237`, the 12 `messenger.bus.pimcore-core` call sites

- Switch the highest-count lookups first; smallest diff with the largest effect.

---

## [ ] DI-03 – Remaining service lookups in `models/`

- Replace the other `Pimcore::getContainer()->get(...)` calls in `models/` with locator accessors, one domain per commit
  (Asset, Document / Editable, DataObject, Element, Translation, …).
- Hoist lookups out of loops where TODO-3 already flags it (metadata loader, Localizedfield).
- Dynamic ids (`'pimcore.implementation_loader.object.' . $datatype`, `substr($class, 1)`) need a dedicated accessor or a
  tagged locator.

---

## [ ] DI-04 – `Pimcore::getEventDispatcher()` in models

- 41 calls → `ModelServices::eventDispatcher()`. Keep `Pimcore::getEventDispatcher()` itself for BC.

---

## [ ] DI-05 – Generated class code

**Files:** `getGetterCode*()` / `getSetterCode*()` in `models/DataObject/ClassDefinition/Data/**`, class builders in
`lib/DataObject/ClassBuilder/**`

- Emit locator calls (or a call into a static `Service` helper) instead of `\Pimcore::getContainer()`.
- Already generated classes keep working because `Pimcore::getContainer()` stays; regenerate via `pimcore:deployment:classes-rebuild`.

---

## [ ] DI-06 – Move behaviour out of models (per domain, opportunistic)

- Long tail, weeks of work, changes public static APIs → only when a domain is touched anyway.
- Candidates: calculated values in `DataObject\Service` (`:1440-1570`), `DataObject\Service` split (TODO-3 ~`:1147`),
  Editable rendering services, `Translation` sanitizer / lookups, Version storage adapter.

---

## [ ] DI-07 – `lib/` and `bundles/` follow-up

- 49 calls in 25 files (`lib/`) and 19 in 12 files (`bundles/`). Services / bundles with a real constructor → plain
  constructor injection, not the locator.
- Optionally add `trigger_deprecation()` to `Pimcore::getContainer()` once core no longer calls it.
