<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;

class ServiceList extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $sname;
    public function __construct($sname)
    {
        $this->sname = $sname;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.service-list');
    }
}
