<?php

namespace Tracks\Provider;

interface ProviderInterface {

    public function push($data);

    public function pop($data);

    public function count($data);

    public function error($data);

}