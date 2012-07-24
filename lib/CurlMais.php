<?php
namespace lib;
/**
 * Classe utilitaria para facilitar trabalhos
 * rotineiros com cURLe possibilitar dar seguir
 * redirecionamentos em ambientes que não permitem
 * CURL_FOLLOWLOCATION
 *
 * @author Jose Berardo
 */
class CurlMais {

    public static $ultimosCabecalhos;
    public static $ultimaURL;
    /**
     * Método para obter uma URL.
     * Padrão:
     * CURLOPT_RETURNTRANSFER
     * CURLOPT_HEADER
     * CURLOPT_FOLLOWLOCATION (mesmo em ambientes safe_mode ou open_basedir)
     * @param string $url
     */
    public static function getURL($url) {
        self::$ultimaURL = $url;
        $go = curl_init($url);
        curl_setopt($go, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13");
        curl_setopt($go, CURLOPT_URL, $url);
        //follow on location problems
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($go, CURLOPT_FOLLOWLOCATION, $l);
            $syn = curl_exec($go);
        } else {
            $syn = self::curlRedirExec($go);
        }
        curl_close($go);
        return $syn;
    }

    //follow on location problems workaround
    private static function curlRedirExec($ch) {
        static $curl_loops = 0;
        static $curl_max_loops = 2;
        if ($curl_loops++ >= $curl_max_loops) {
            $curl_loops = 0;
            return false;
        }
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        // transformando quebras de linha Windows em quebras Unix
        $data = str_replace("\r\n", "\n", $data);
        preg_match("/[\r\n]{2}/", $data, $matches, \PREG_OFFSET_CAPTURE);

        // Obtendo o que vem antes da primeira linha em branco
        $header = substr($data, 0, $matches[0][1]);
        // Obtendo o que vem após a primeira linha em branco
        $data = substr($data, $matches[0][1] + 2);
        // Obtendo os cabeçalhos em array linha a linha
        self::$ultimosCabecalhos = preg_split("/[\r\n]/", $header);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
            $matches = array();
            preg_match('/Location:(.*?)\n/', $header, $matches);
            $url = @parse_url(trim(array_pop($matches)));
            if (!$url) {
                //couldn't process the url to redirect to
                $curl_loops = 0;
                return $data;
            }
            $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            if (empty($url['scheme']))
                $url['scheme'] = $last_url['scheme'];
            if (empty($url['host']))
                $url['host'] = $last_url['host'];
            if (empty($url['path']))
                $url['path'] = $last_url['path'];
            $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . (@$url['query'] ? '?' . @$url['query'] : '');
            curl_setopt($ch, CURLOPT_URL, $new_url);
            self::$ultimaURL = $new_url;
            return self::curlRedirExec($ch);
        } else {
            $curl_loops = 0;
            return $data;
        }
    }
}