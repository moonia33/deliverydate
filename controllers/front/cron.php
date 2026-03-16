<?php

class DeliverydateCronModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        header('Content-Type: text/plain; charset=utf-8');

        $expected = (string) Configuration::get(Deliverydate::CONFIG_CRON_TOKEN);
        $token = (string) Tools::getValue('token');

        if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            exit('Bad token');
        }

        if (!Module::isEnabled('deliverydate')) {
            http_response_code(409);
            exit('Module disabled');
        }

        $ok = (bool) $this->module->runUpdate();
        if (!$ok) {
            http_response_code(500);
            exit('Update failed');
        }

        exit('OK');
    }
}
