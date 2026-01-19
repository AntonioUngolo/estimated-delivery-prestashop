<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class EstimatedDelivery extends Module
{
    public function __construct()
    {
        $this->name = 'estimateddelivery';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Antonio';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Data di Consegna Stimata');
        $this->description = $this->l('Mostra la data di consegna stimata nella scheda prodotto escludendo giorni festivi e weekend');

        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare questo modulo?');
    }

    public function install()
    {
        Configuration::updateValue('ESTIMATED_DELIVERY_DAYS', 2);
        Configuration::updateValue('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY', 1);
        Configuration::updateValue('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY', 1);
        Configuration::updateValue('ESTIMATED_DELIVERY_HOLIDAYS', $this->getDefaultItalianHolidays());
        Configuration::updateValue('ESTIMATED_DELIVERY_ENABLED', 1);
        Configuration::updateValue('ESTIMATED_DELIVERY_TEXT', 'Consegna prevista entro il: {date}');
        
        // Nuove configurazioni per il countdown
        Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED', 1);
        Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_HOUR', 14);
        Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_DAYS', 1);
        Configuration::updateValue('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS', 3);
        Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_TEXT', 'Ordina entro {countdown} e ricevi il prodotto il {date}');
        Configuration::updateValue('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT', 'Ordina oggi e lo ricevi il {date}');
        
        return parent::install() &&
            $this->registerHook('displayProductAdditionalInfo') &&
            $this->registerHook('displayReassurance') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        Configuration::deleteByName('ESTIMATED_DELIVERY_DAYS');
        Configuration::deleteByName('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY');
        Configuration::deleteByName('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY');
        Configuration::deleteByName('ESTIMATED_DELIVERY_HOLIDAYS');
        Configuration::deleteByName('ESTIMATED_DELIVERY_ENABLED');
        Configuration::deleteByName('ESTIMATED_DELIVERY_TEXT');
        Configuration::deleteByName('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED');
        Configuration::deleteByName('ESTIMATED_DELIVERY_COUNTDOWN_HOUR');
        Configuration::deleteByName('ESTIMATED_DELIVERY_COUNTDOWN_DAYS');
        Configuration::deleteByName('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS');
        Configuration::deleteByName('ESTIMATED_DELIVERY_COUNTDOWN_TEXT');
        Configuration::deleteByName('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT');

        return parent::uninstall();
    }

    /**
     * Festività italiane predefinite (anno corrente)
     */
    private function getDefaultItalianHolidays()
    {
        $year = date('Y');
        $holidays = [
            $year . '-01-01', // Capodanno
            $year . '-01-06', // Epifania
            $year . '-04-25', // Liberazione
            $year . '-05-01', // Festa del Lavoro
            $year . '-06-02', // Festa della Repubblica
            $year . '-08-15', // Ferragosto
            $year . '-11-01', // Tutti i Santi
            $year . '-12-08', // Immacolata
            $year . '-12-25', // Natale
            $year . '-12-26', // Santo Stefano
        ];

        // Aggiungi Pasqua e Pasquetta (calcolo dinamico)
        $easter = easter_date($year);
        $holidays[] = date('Y-m-d', $easter);
        $holidays[] = date('Y-m-d', strtotime('+1 day', $easter));

        return implode(',', $holidays);
    }

    /**
     * Configurazione del modulo
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitEstimatedDeliveryConfig')) {
            $deliveryDays = (int)Tools::getValue('ESTIMATED_DELIVERY_DAYS');
            $excludeSaturday = (int)Tools::getValue('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY');
            $excludeSunday = (int)Tools::getValue('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY');
            $holidays = Tools::getValue('ESTIMATED_DELIVERY_HOLIDAYS');
            $enabled = (int)Tools::getValue('ESTIMATED_DELIVERY_ENABLED');
            $text = Tools::getValue('ESTIMATED_DELIVERY_TEXT');
            
            $countdownEnabled = (int)Tools::getValue('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED');
            $countdownHour = (int)Tools::getValue('ESTIMATED_DELIVERY_COUNTDOWN_HOUR');
            $countdownDays = (int)Tools::getValue('ESTIMATED_DELIVERY_COUNTDOWN_DAYS');
            $afterHoursDays = (int)Tools::getValue('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS');
            $countdownText = Tools::getValue('ESTIMATED_DELIVERY_COUNTDOWN_TEXT');
            $afterHoursText = Tools::getValue('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT');

            if ($deliveryDays < 1) {
                $output .= $this->displayError($this->l('I giorni lavorativi devono essere almeno 1'));
            } else {
                Configuration::updateValue('ESTIMATED_DELIVERY_DAYS', $deliveryDays);
                Configuration::updateValue('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY', $excludeSaturday);
                Configuration::updateValue('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY', $excludeSunday);
                Configuration::updateValue('ESTIMATED_DELIVERY_HOLIDAYS', $holidays);
                Configuration::updateValue('ESTIMATED_DELIVERY_ENABLED', $enabled);
                Configuration::updateValue('ESTIMATED_DELIVERY_TEXT', $text);
                
                Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED', $countdownEnabled);
                Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_HOUR', $countdownHour);
                Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_DAYS', $countdownDays);
                Configuration::updateValue('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS', $afterHoursDays);
                Configuration::updateValue('ESTIMATED_DELIVERY_COUNTDOWN_TEXT', $countdownText);
                Configuration::updateValue('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT', $afterHoursText);
                
                $output .= $this->displayConfirmation($this->l('Impostazioni salvate con successo'));
            }
        }

        return $output . $this->displayForm();
    }

    /**
     * Form di configurazione
     */
    public function displayForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configurazione Data di Consegna'),
                    'icon' => 'icon-calendar'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Abilita modulo'),
                        'name' => 'ESTIMATED_DELIVERY_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Sì')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_1',
                        'html_content' => '<hr><h4>Impostazioni Generali Consegna</h4>',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Giorni lavorativi per la consegna'),
                        'name' => 'ESTIMATED_DELIVERY_DAYS',
                        'size' => 5,
                        'required' => true,
                        'desc' => $this->l('Numero di giorni lavorativi necessari per la consegna (esclusi weekend e festivi)')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Escludi sabato'),
                        'name' => 'ESTIMATED_DELIVERY_EXCLUDE_SATURDAY',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'saturday_on',
                                'value' => 1,
                                'label' => $this->l('Sì')
                            ],
                            [
                                'id' => 'saturday_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Escludi domenica'),
                        'name' => 'ESTIMATED_DELIVERY_EXCLUDE_SUNDAY',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'sunday_on',
                                'value' => 1,
                                'label' => $this->l('Sì')
                            ],
                            [
                                'id' => 'sunday_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Giorni festivi'),
                        'name' => 'ESTIMATED_DELIVERY_HOLIDAYS',
                        'rows' => 10,
                        'cols' => 60,
                        'desc' => $this->l('Inserisci i giorni festivi in formato YYYY-MM-DD, uno per riga o separati da virgola. Es: 2025-01-01, 2025-12-25')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Testo da mostrare'),
                        'name' => 'ESTIMATED_DELIVERY_TEXT',
                        'size' => 60,
                        'desc' => $this->l('Usa {date} come segnaposto per la data. Es: "Consegna prevista entro il: {date}"')
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_2',
                        'html_content' => '<hr><h4>Impostazioni Countdown Urgenza</h4>',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Abilita countdown'),
                        'name' => 'ESTIMATED_DELIVERY_COUNTDOWN_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'countdown_on',
                                'value' => 1,
                                'label' => $this->l('Sì')
                            ],
                            [
                                'id' => 'countdown_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                        'desc' => $this->l('Mostra un countdown per incentivare ordini rapidi')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Ora limite countdown (0-23)'),
                        'name' => 'ESTIMATED_DELIVERY_COUNTDOWN_HOUR',
                        'size' => 5,
                        'required' => true,
                        'desc' => $this->l('Ora del giorno fino alla quale il countdown è attivo (es: 14 per le 14:00)')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Giorni consegna entro countdown'),
                        'name' => 'ESTIMATED_DELIVERY_COUNTDOWN_DAYS',
                        'size' => 5,
                        'required' => true,
                        'desc' => $this->l('Giorni lavorativi di consegna se si ordina prima dell\'ora limite')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Giorni consegna dopo countdown'),
                        'name' => 'ESTIMATED_DELIVERY_AFTER_HOURS_DAYS',
                        'size' => 5,
                        'required' => true,
                        'desc' => $this->l('Giorni lavorativi di consegna se si ordina dopo l\'ora limite')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Testo durante countdown'),
                        'name' => 'ESTIMATED_DELIVERY_COUNTDOWN_TEXT',
                        'size' => 80,
                        'desc' => $this->l('Usa {countdown} per il countdown e {date} per la data. Es: "Ordina entro {countdown} e ricevi il prodotto il {date}"')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Testo dopo ora limite'),
                        'name' => 'ESTIMATED_DELIVERY_AFTER_HOURS_TEXT',
                        'size' => 80,
                        'desc' => $this->l('Usa {date} per la data. Es: "Ordina oggi e lo ricevi il {date}"')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Salva'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEstimatedDeliveryConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Valori del form di configurazione
     */
    public function getConfigFormValues()
    {
        return [
            'ESTIMATED_DELIVERY_DAYS' => Configuration::get('ESTIMATED_DELIVERY_DAYS'),
            'ESTIMATED_DELIVERY_EXCLUDE_SATURDAY' => Configuration::get('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY'),
            'ESTIMATED_DELIVERY_EXCLUDE_SUNDAY' => Configuration::get('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY'),
            'ESTIMATED_DELIVERY_HOLIDAYS' => Configuration::get('ESTIMATED_DELIVERY_HOLIDAYS'),
            'ESTIMATED_DELIVERY_ENABLED' => Configuration::get('ESTIMATED_DELIVERY_ENABLED'),
            'ESTIMATED_DELIVERY_TEXT' => Configuration::get('ESTIMATED_DELIVERY_TEXT'),
            'ESTIMATED_DELIVERY_COUNTDOWN_ENABLED' => Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED'),
            'ESTIMATED_DELIVERY_COUNTDOWN_HOUR' => Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_HOUR'),
            'ESTIMATED_DELIVERY_COUNTDOWN_DAYS' => Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_DAYS'),
            'ESTIMATED_DELIVERY_AFTER_HOURS_DAYS' => Configuration::get('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS'),
            'ESTIMATED_DELIVERY_COUNTDOWN_TEXT' => Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_TEXT'),
            'ESTIMATED_DELIVERY_AFTER_HOURS_TEXT' => Configuration::get('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT'),
        ];
    }

    /**
     * Calcola la data di consegna stimata
     * 
     * @param string|null $startDate Data di partenza (formato Y-m-d), se null usa oggi
     * @param int|null $customDeliveryDays Giorni lavorativi personalizzati, se null usa la configurazione
     * @return DateTime|null
     */
    public function calculateEstimatedDelivery($startDate = null, $customDeliveryDays = null)
    {
        if (!Configuration::get('ESTIMATED_DELIVERY_ENABLED')) {
            return null;
        }

        // Usa giorni personalizzati o quelli della configurazione
        $deliveryDays = $customDeliveryDays !== null 
            ? (int)$customDeliveryDays 
            : (int)Configuration::get('ESTIMATED_DELIVERY_DAYS');
            
        $excludeSaturday = (bool)Configuration::get('ESTIMATED_DELIVERY_EXCLUDE_SATURDAY');
        $excludeSunday = (bool)Configuration::get('ESTIMATED_DELIVERY_EXCLUDE_SUNDAY');
        $holidaysString = Configuration::get('ESTIMATED_DELIVERY_HOLIDAYS');
        
        // Parse holidays
        $holidays = [];
        if (!empty($holidaysString)) {
            $holidaysArray = preg_split('/[\s,\n\r]+/', $holidaysString);
            foreach ($holidaysArray as $holiday) {
                $holiday = trim($holiday);
                if (!empty($holiday) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday)) {
                    $holidays[] = $holiday;
                }
            }
        }

        // Data di partenza (oggi se non specificato)
        $currentDate = $startDate ? new DateTime($startDate) : new DateTime();
        $workingDaysAdded = 0;

        // Aggiungi giorni lavorativi
        while ($workingDaysAdded < $deliveryDays) {
            $currentDate->modify('+1 day');
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = (int)$currentDate->format('N'); // 1 = Monday, 7 = Sunday

            // Controlla se è weekend
            $isWeekend = false;
            if ($excludeSaturday && $dayOfWeek == 6) {
                $isWeekend = true;
            }
            if ($excludeSunday && $dayOfWeek == 7) {
                $isWeekend = true;
            }

            // Controlla se è festivo
            $isHoliday = in_array($dateString, $holidays);

            // Se non è weekend e non è festivo, conta come giorno lavorativo
            if (!$isWeekend && !$isHoliday) {
                $workingDaysAdded++;
            }
        }

        return $currentDate;
    }

    /**
     * Hook nella pagina prodotto
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        if (!Configuration::get('ESTIMATED_DELIVERY_ENABLED')) {
            return '';
        }

        $countdownEnabled = Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_ENABLED');
        $countdownHour = (int)Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_HOUR');
        
        // Ottieni l'ora corrente
        $now = new DateTime();
        $currentHour = (int)$now->format('H');
        
        $showCountdown = false;
        $displayText = '';
        $deliveryDays = 0;
        $countdownEndTime = null;
        $estimatedDate = null;
        
        if ($countdownEnabled && $currentHour < $countdownHour) {
            // Siamo prima dell'ora limite - mostra countdown
            $showCountdown = true;
            $deliveryDays = (int)Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_DAYS');
            
            // Calcola la data di consegna stimata
            $estimatedDate = $this->calculateEstimatedDelivery(null, $deliveryDays);
            
            if (!$estimatedDate) {
                return '';
            }
            
            // Calcola quando finisce il countdown (oggi alle countdownHour)
            $countdownEndTime = new DateTime();
            $countdownEndTime->setTime($countdownHour, 0, 0);
            
            // Formatta la data in italiano
            $formatter = new IntlDateFormatter(
                'it_IT',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE
            );
            $formattedDate = $formatter->format($estimatedDate->getTimestamp());
            
            $text = Configuration::get('ESTIMATED_DELIVERY_COUNTDOWN_TEXT');
            $displayText = str_replace('{date}', $formattedDate, $text);
            $displayText = str_replace('{days}', $deliveryDays, $displayText);
            // {countdown} verrà gestito in JavaScript
            
        } elseif ($countdownEnabled && $currentHour >= $countdownHour) {
            // Siamo dopo l'ora limite - mostra data di consegna ritardata
            $showCountdown = false;
            $deliveryDays = (int)Configuration::get('ESTIMATED_DELIVERY_AFTER_HOURS_DAYS');
            
            // Calcola la data di consegna stimata
            $estimatedDate = $this->calculateEstimatedDelivery(null, $deliveryDays);
            
            if (!$estimatedDate) {
                return '';
            }
            
            // Formatta la data in italiano
            $formatter = new IntlDateFormatter(
                'it_IT',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE
            );
            $formattedDate = $formatter->format($estimatedDate->getTimestamp());
            
            $text = Configuration::get('ESTIMATED_DELIVERY_AFTER_HOURS_TEXT');
            $displayText = str_replace('{date}', $formattedDate, $text);
            $displayText = str_replace('{days}', $deliveryDays, $displayText);
            
        } else {
            // Countdown disabilitato - mostra data stimata normale
            $estimatedDate = $this->calculateEstimatedDelivery();
            
            if (!$estimatedDate) {
                return '';
            }

            $formatter = new IntlDateFormatter(
                'it_IT',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE
            );
            $formattedDate = $formatter->format($estimatedDate->getTimestamp());

            $text = Configuration::get('ESTIMATED_DELIVERY_TEXT');
            $displayText = str_replace('{date}', $formattedDate, $text);
        }

        $this->context->smarty->assign([
            'display_text' => $displayText,
            'show_countdown' => $showCountdown,
            'countdown_end_time' => $showCountdown ? $countdownEndTime->format('Y-m-d H:i:s') : null,
            'delivery_days' => $deliveryDays,
            'estimated_date' => $estimatedDate ? $estimatedDate->format('Y-m-d') : null,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product.tpl');
    }

    /**
     * Hook header per CSS
     */
    public function hookHeader()
    {
        if (!Configuration::get('ESTIMATED_DELIVERY_ENABLED')) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'modules-estimateddelivery',
            'modules/' . $this->name . '/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    /**
     * Hook Reassurance
     */
    public function hookDisplayReassurance($params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }

    /**
     * Hook Footer Product
     */
    public function hookDisplayFooterProduct($params)
    {
        return $this->hookDisplayProductAdditionalInfo($params);
    }
}