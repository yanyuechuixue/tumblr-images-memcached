<?php

/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2016-04-23
 * Time: 23:34
 */
class HANDLER {
    /**
     * @var mc $mc;
     */
    private static $mc;

    public static function loadMC($mc = null) {
        !static::$mc && (static::$mc = $mc ?: new mc());
    }

    public static function handle($url) {

        $INFO = null;
        $param = null;

        try {
            $param = TOOL::URLParam($url);
            $resINFO = IN::resINFO($param);

            if ($resINFO) {
                # it is a HEAD request
                if (TOOL::isREQMethod('HEAD')) {
                    TOOL::log('HEAD Response');

                    OUT::headers($resINFO['HEAD']);
                }

                else {
                    TOOL::log('GET Response');

                    switch ($resINFO['TYPE']) {
                        case 'HTMLZip':
                            OUT::ZIP($resINFO['CONTENT']);
                        break;
                        case 'SOURCE':
                            OUT::redirect($resINFO['CONTENT']);
                        break;
                    }
                }
            }

            else {
                $JSON = IN::JSON($param);
                $type = strtolower($JSON['type']);
                $method = ucfirst($type);
                switch ($type) {
                    case 'audio':
                    case 'answer':
                    case 'conversation':
                    case 'link':
                    case 'regular':
                    case 'quote':
                        $HTML = PARSER::$method($JSON);
                        $ZIP  = TOOL::HTMLZip($HTML);
                        $INFO = ['CONTENT' => $ZIP, 'TYPE' => 'HTMLZip'];

                    OUT::ZIP($ZIP, TOOL::isREQMethod('HEAD'));
                    break;

                    case 'video':
                        $SOURCE = PARSER::$method($JSON);
                        $INFO = ['CONTENT' => $SOURCE, 'TYPE' => 'SOURCE'];

                        OUT::redirect($SOURCE);
                    break;

                    case 'unknow':
                    case 'photo':
                        $URLs = PARSER::$method($JSON);

                        $num = count($URLs);
                        if ($num === 1) {
                            $SOURCE = $URLs[0];
                            $INFO = ['CONTENT' => $SOURCE, 'TYPE' => 'SOURCE'];

                            OUT::redirect($SOURCE);
                        }

                        else {

                            if (CONF_PACKIMGS) {

                                TOOL::cleanLastWeek();

                                $IMGsPack = IN::getIMGs($URLs);
                                $ZIP = TOOL::IMGZip($IMGsPack);

                                OUT::saveIMGs($IMGsPack);
                                OUT::ZIP($ZIP, TOOL::isREQMethod('HEAD'));

                                $INFO = ['TYPE' => 'PHOTOS'];
                            }

                            else {
                                $HTML = TOOL::IMGsPage($URLs);
                                $ZIP = TOOL::HTMLZip($HTML);
                                $INFO = ['CONTENT' => $ZIP, 'TYPE' => 'HTMLZip'];

                                OUT::ZIP($ZIP);
                            }
                        }
                    break;
                }
            }
        }

        catch (Exception $e) {

            $errText = TOOL::errText($e->getMessage());

            OUT::TEXT($errText);
        }

        finally {
            if ($INFO) {

                $INFO['HEAD'] = headers_list();

                OUT::resINFO($param, $INFO);

            }
        }
    }

}