<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;

class CityList extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $cname;
    public function __construct($cname)
    {
        $this->cname = $cname;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $CityList = DB::table('city')->get();
        return view('components.city-list')->with('CityList', $CityList);
    }
}
