<?php

namespace Unic\Server;

trait ServerTrait
{
    public function useOpenSwooleApp($instance, array $options = [])
    {
        $this->config->set('server', 'openswoole', [
            'server_instance' => $instance,
            'options' => $options,
        ]);
        return $this;
    }
}
