<?php

namespace Walletable\Exceptions;

/**
 * Deprecated misspelling of {@see IncompatibleWalletsException}.
 *
 * This is an alias rather than a subclass, so the two names resolve to the
 * same class: existing `catch (IncompactibleWalletsException $e)` blocks keep
 * working even though the package now throws the correctly spelled name.
 *
 * @deprecated Use \Walletable\Exceptions\IncompatibleWalletsException. Removed in the next major.
 */
class_alias(IncompatibleWalletsException::class, IncompactibleWalletsException::class);
