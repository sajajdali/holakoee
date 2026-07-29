<?php

namespace Modules\Transaction\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Transaction\Entities\Transaction;

class TransactionIndex extends Component
{

    use WithPagination;
    public function render()
    {
        return view('transaction::livewire.transaction-index',['transactions'=>Transaction::latest()->paginate(50)]);
    }
}
