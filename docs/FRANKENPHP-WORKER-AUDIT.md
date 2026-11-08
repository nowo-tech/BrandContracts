# FrankenPHP worker audit (kernel not reset)

The package is safe when FrankenPHP keeps the application warm **without** resetting it between requests.

## Table of contents

- [What keeps state](#what-keeps-state)
- [Checks in CI](#checks-in-ci)
- [Provider and consumer responsibilities](#provider-and-consumer-responsibilities)

## What keeps state

Nothing. Value objects are `final readonly`; helper classes are stateless with static pure functions; there is no
static property, no memo and no singleton.

## Checks in CI

- `nowo-tech/phpstan-frankenphp` (classic, worker-strict and hardening rulesets) at PHPStan level max.
- Igor (`composer igor`).

## Provider and consumer responsibilities

A provider that memoises the `Brand` per request must reset that memo between requests (Symfony `ResetInterface`), so a
worker never serves a stale brand after an admin changes it. `Brand::$version` changes whenever any field changes and
can key such caches.
