<?php

namespace Walletable;

use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Walletable\Contracts\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Actions\Traits\HasActions;
use Walletable\Internals\Creator;
use Walletable\Internals\Lockers\Traits\HasLockers;
use Walletable\Ledger\PostingDraft;
use Walletable\Ledger\Traits\HasTransactionColumns;
use Walletable\Models\Wallet;

class WalletableManager
{
    use Macroable;
    use HasLockers;
    use HasActions;
    use HasTransactionColumns;

    /**
     * Map of resolved class names back to their registered key. Populated by
     * HasActions / HasLockers as resolutions happen.
     *
     * @var array<string,string>
     */
    protected $classMap = [];

    public function create(
        Walletable $walletable,
        string $label,
        string $tag,
        string $currency
    ): Wallet {
        $creator = new Creator($walletable);

        return $creator
            ->name($walletable->getOwnerName())
            ->email($walletable->getOwnerEmail())
            ->label($label)
            ->tag($tag)
            ->currency($currency)->create();
    }

    public function compactible(Wallet $wallet, Wallet $against): bool
    {
        return $wallet->currency->getCode() === $against->currency->getCode();
    }

    /**
     * Apply an action to one or more posting drafts.
     *
     * @param ActionInterface|string $action
     * @param PostingDraft|PostingDraft[] $postings
     */
    public function applyAction($action, $postings, ActionData $data): void
    {
        if (!is_string($action) && !($action instanceof ActionInterface)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 must be of type %s or string',
                ActionInterface::class
            ));
        }

        if (is_string($action)) {
            $action = $this->action($action);
        }

        if ($postings instanceof PostingDraft) {
            $action->apply($postings, $data);
            return;
        }

        if (is_array($postings)) {
            foreach ($postings as $p) {
                if (!($p instanceof PostingDraft)) {
                    throw new InvalidArgumentException(sprintf(
                        'Each posting must be an instance of %s',
                        PostingDraft::class
                    ));
                }
                $action->apply($p, $data);
            }
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Argument 2 must be a %s or an array of %1$s',
            PostingDraft::class
        ));
    }
}
