<?php

namespace Unic\Server;

trait ServerTrait
{
    public function useDefaultServer($instance, array $options = [])
    {
        $this->config->set('server', 'php', [
            'server_instance' => $instance,
            'options' => $options,
        ]);
        return $this;
    }

    public function useOpenSwooleServer($instance, array $options = [])
    {
        $this->config->set('server', 'openswoole', [
            'server_instance' => $instance,
            'options' => $options,
        ]);
        return $this;
    }
}
