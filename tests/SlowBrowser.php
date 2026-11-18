<?php

namespace Tests;

use Laravel\Dusk\Browser;

class SlowBrowser extends Browser
{
    protected function slow()
    {
        $ms = (int) env('DUSK_SLOW_MS', 0);

        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    public function visit($url)
    {
        $result = parent::visit($url);
        $this->slow();
        return $result;
    }

    public function click($selector = null, $button = 'left')
    {
        $result = parent::click($selector, $button);
        $this->slow();
        return $result;
    }

    public function clickLink($link, $selector = null)
    {
        $result = parent::clickLink($link, $selector);
        $this->slow();
        return $result;
    }

    public function type($field, $value)
    {
        $result = parent::type($field, $value);
        $this->slow();
        return $result;
    }

    public function press($button)
    {
        $result = parent::press($button);
        $this->slow();
        return $result;
    }

    public function select($field, $value = null)
    {
        $result = parent::select($field, $value);
        $this->slow();
        return $result;
    }

    public function check($field, $value = null)
    {
        $result = parent::check($field, $value);
        $this->slow();
        return $result;
    }

    public function radio($field, $value)
    {
        $result = parent::radio($field, $value);
        $this->slow();
        return $result;
    }

    public function attach($field, $path)
    {
        $result = parent::attach($field, $path);
        $this->slow();
        return $result;
    }
}