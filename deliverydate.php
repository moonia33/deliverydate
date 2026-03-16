<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Deliverydate extends Module
{
    public const CONFIG_MODE = 'DELIVERYDATE_MODE';
    public const CONFIG_DAYS = 'DELIVERYDATE_DAYS';
    public const CONFIG_DATE = 'DELIVERYDATE_DATE';
    public const CONFIG_CRON_TOKEN = 'DELIVERYDATE_CRON_TOKEN';

    private const MODE_DAYS = 'days';
    private const MODE_DATE = 'date';

    public function __construct()
    {
        $this->name = 'deliverydate';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.1';
        $this->author = 'moonia';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Pristatymo data', [], 'Modules.Deliverydate.Admin');
        $this->description = $this->trans('Rankiniu būdu arba per cron atnaujina „Prekių, esančių sandėlyje, pristatymo laiką“ ir kurjerių pristatymo laiką.', [], 'Modules.Deliverydate.Admin');

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];
    }

    public function install()
    {
        return parent::install()
            && $this->initConfig();
    }

    public function uninstall()
    {
        return $this->deleteConfig() && parent::uninstall();
    }

    private function initConfig()
    {
        if (!Configuration::get(self::CONFIG_MODE)) {
            Configuration::updateValue(self::CONFIG_MODE, self::MODE_DAYS);
        }

        if (Configuration::get(self::CONFIG_DAYS) === false) {
            Configuration::updateValue(self::CONFIG_DAYS, 0);
        }

        if (Configuration::get(self::CONFIG_DATE) === false) {
            Configuration::updateValue(self::CONFIG_DATE, '');
        }

        if (!Configuration::get(self::CONFIG_CRON_TOKEN)) {
            Configuration::updateValue(self::CONFIG_CRON_TOKEN, Tools::passwdGen(32));
        }

        return true;
    }

    private function deleteConfig()
    {
        return Configuration::deleteByName(self::CONFIG_MODE)
            && Configuration::deleteByName(self::CONFIG_DAYS)
            && Configuration::deleteByName(self::CONFIG_DATE)
            && Configuration::deleteByName(self::CONFIG_CRON_TOKEN);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitDeliverydate')) {
            $mode = (string) Tools::getValue(self::CONFIG_MODE);
            $days = Tools::getValue(self::CONFIG_DAYS);
            $date = (string) Tools::getValue(self::CONFIG_DATE);

            $errors = $this->validateConfig($mode, $days, $date);
            if (!empty($errors)) {
                $output .= $this->displayError(implode('<br>', $errors));
            } else {
                Configuration::updateValue(self::CONFIG_MODE, $mode);
                Configuration::updateValue(self::CONFIG_DAYS, (int) $days);
                Configuration::updateValue(self::CONFIG_DATE, $date);

                if ($this->runUpdate()) {
                    $output .= $this->displayConfirmation(
                        $this->trans('Nustatymai išsaugoti ir pristatymo laikas atnaujintas.', [], 'Modules.Deliverydate.Admin')
                    );
                } else {
                    $output .= $this->displayError(
                        $this->trans('Nustatymai išsaugoti, bet nepavyko atnaujinti pristatymo laiko.', [], 'Modules.Deliverydate.Admin')
                    );
                }
            }
        }

        if (Tools::isSubmit('submitDeliverydateUpdateNow')) {
            if ($this->runUpdate()) {
                $output .= $this->displayConfirmation(
                    $this->trans('Pristatymo laikas atnaujintas.', [], 'Modules.Deliverydate.Admin')
                );
            } else {
                $output .= $this->displayError(
                    $this->trans('Nepavyko atnaujinti pristatymo laiko.', [], 'Modules.Deliverydate.Admin')
                );
            }
        }

        $output .= $this->renderForm();
        $output .= $this->renderCronInfo();

        return $output;
    }

    /**
     * @param string $mode
     * @param mixed $days
     * @param string $date
     *
     * @return string[]
     */
    private function validateConfig($mode, $days, $date)
    {
        $errors = [];

        if ($mode !== self::MODE_DAYS && $mode !== self::MODE_DATE) {
            $errors[] = $this->trans('Neteisingas režimas.', [], 'Modules.Deliverydate.Admin');
            return $errors;
        }

        if ($mode === self::MODE_DAYS) {
            if ($days === '' || $days === null) {
                $days = 0;
            }

            if (!Validate::isUnsignedInt($days)) {
                $errors[] = $this->trans('Dienų skaičius turi būti neneigiamas sveikasis skaičius.', [], 'Modules.Deliverydate.Admin');
            }
        }

        if ($mode === self::MODE_DATE) {
            if ($date === '') {
                $errors[] = $this->trans('Nurodykite konkrečią datą.', [], 'Modules.Deliverydate.Admin');
            } elseif (!Validate::isDateFormat($date)) {
                $errors[] = $this->trans('Data turi būti formatu YYYY-MM-DD.', [], 'Modules.Deliverydate.Admin');
            }
        }

        return $errors;
    }

    /**
     * @return string
     */
    private function renderForm()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Nustatymai', [], 'Modules.Deliverydate.Admin'),
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->trans('Režimas', [], 'Modules.Deliverydate.Admin'),
                        'name' => self::CONFIG_MODE,
                        'values' => [
                            [
                                'id' => 'deliverydate_mode_days',
                                'value' => self::MODE_DAYS,
                                'label' => $this->trans('Pridėti dienų nuo šiandien', [], 'Modules.Deliverydate.Admin'),
                            ],
                            [
                                'id' => 'deliverydate_mode_date',
                                'value' => self::MODE_DATE,
                                'label' => $this->trans('Konkreti data', [], 'Modules.Deliverydate.Admin'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Dienų skaičius', [], 'Modules.Deliverydate.Admin'),
                        'name' => self::CONFIG_DAYS,
                        'class' => 'fixed-width-xs',
                        'desc' => $this->trans('Naudojama tik kai pasirinktas „dienų“ režimas.', [], 'Modules.Deliverydate.Admin'),
                    ],
                    [
                        'type' => 'date',
                        'label' => $this->trans('Konkreti data', [], 'Modules.Deliverydate.Admin'),
                        'name' => self::CONFIG_DATE,
                        'desc' => $this->trans('Naudojama tik kai pasirinktas „konkreti data“ režimas.', [], 'Modules.Deliverydate.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Išsaugoti', [], 'Admin.Actions'),
                ],
                'buttons' => [
                    [
                        'type' => 'submit',
                        'title' => $this->trans('Atnaujinti dabar', [], 'Modules.Deliverydate.Admin'),
                        'icon' => 'process-icon-refresh',
                        'name' => 'submitDeliverydateUpdateNow',
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDeliverydate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @return array
     */
    private function getConfigFieldsValues()
    {
        $token = (string) Configuration::get(self::CONFIG_CRON_TOKEN);
        if ($token === '') {
            Configuration::updateValue(self::CONFIG_CRON_TOKEN, Tools::passwdGen(32));
        }

        return [
            self::CONFIG_MODE => Configuration::get(self::CONFIG_MODE) ?: self::MODE_DAYS,
            self::CONFIG_DAYS => (int) Configuration::get(self::CONFIG_DAYS),
            self::CONFIG_DATE => (string) Configuration::get(self::CONFIG_DATE),
        ];
    }

    /**
     * @return string
     */
    private function renderCronInfo()
    {
        $cronUrl = $this->getCronUrl();

        return '<div class="panel">
            <h3>' . $this->trans('Cron', [], 'Modules.Deliverydate.Admin') . '</h3>
            <p>' . $this->trans('Naudokite šį URL automatiniam atnaujinimui (pvz. kasdien):', [], 'Modules.Deliverydate.Admin') . '</p>
            <p><code>' . htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') . '</code></p>
        </div>';
    }

    /**
     * @return string
     */
    public function getCronUrl()
    {
        $token = (string) Configuration::get(self::CONFIG_CRON_TOKEN);
        if ($token === '') {
            $token = Tools::passwdGen(32);
            Configuration::updateValue(self::CONFIG_CRON_TOKEN, $token);
        }

        return $this->context->link->getModuleLink($this->name, 'cron', ['token' => $token], true);
    }

    /**
     * @return string|null
     */
    private function getComputedDate()
    {
        $mode = (string) Configuration::get(self::CONFIG_MODE);

        if ($mode === self::MODE_DATE) {
            $date = (string) Configuration::get(self::CONFIG_DATE);
            if (Validate::isDateFormat($date)) {
                return $date;
            }

            return null;
        }

        $days = (int) Configuration::get(self::CONFIG_DAYS);
        if ($days < 0) {
            $days = 0;
        }

        $tz = Configuration::get('PS_TIMEZONE');
        try {
            $timezone = $tz ? new DateTimeZone($tz) : new DateTimeZone('UTC');
        } catch (Exception $e) {
            $timezone = new DateTimeZone('UTC');
        }

        $dt = new DateTime('now', $timezone);
        if ($days !== 0) {
            $dt->modify('+' . $days . ' days');
        }

        return $dt->format('Y-m-d');
    }

    /**
     * Atnaujina pristatymo datą:
     * - nustatymą „Prekių, esančių sandėlyje, pristatymo laikas“ (`PS_LABEL_DELIVERY_TIME_AVAILABLE`)
     * - kurjerių `delay` (`carrier_lang.delay`)
     *
     * Naudojama tiek rankiniu būdu (iš nustatymų), tiek per cron.
     *
     * @return bool
     */
    public function runUpdate()
    {
        $date = $this->getComputedDate();
        if (!$date) {
            return false;
        }

        return $this->applyDateToStore($date);
    }

    /**
     * @param string $date Y-m-d
     *
     * @return bool
     */
    private function applyDateToStore($date)
    {
        $dateSql = pSQL($date);

        $deliveryTimeLabels = [];
        foreach (Language::getLanguages(false) as $lang) {
            $deliveryTimeLabels[(int) $lang['id_lang']] = $dateSql;
        }

        $ok = Configuration::updateValue('PS_LABEL_DELIVERY_TIME_AVAILABLE', $deliveryTimeLabels);

        $ok = $ok && Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'carrier_lang` cl
            INNER JOIN `' . _DB_PREFIX_ . 'carrier` c ON c.`id_carrier` = cl.`id_carrier`
            SET cl.`delay` = \'' . $dateSql . '\'
            WHERE c.`deleted` = 0
        ');

        return (bool) $ok;
    }

    /**
     * Produktų puslapyje rodymas vyksta per PrestaShop core kintamąjį
     * `$product.delivery_information` (naudojant `PS_LABEL_DELIVERY_TIME_AVAILABLE`).
     * Šis hook'as paliktas dėl suderinamumo, bet sąmoningai nieko nerenderina,
     * kad nedubliuotų informacijos temoje.
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        return '';
    }
}
