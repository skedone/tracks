<?php

namespace Tracks\Provider;

interface ProviderInterface {

    public function pop();

    public function count();

    public function push(array $data);

    public function error($data);

}