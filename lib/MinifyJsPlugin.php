<?php

namespace lib;

/**
 * Classe plugin para carregar o MinifyJs
 */
class MinifyJsPlugin implements IPlugin {

    /**
     * @var MinifyJsPlugin
     */
    private static $instance;

    public function __construct() {
    }

    /**
     * Método para carregar o objeto MinifyJsPlugin
     * @return MinifyJs
     */
    public function carregar() {
        require(LIB . DS . "MinifyJs/MinifyJs.php");
    }
    
    /**
     * Método para minimizar a saída de um documento
     * JS ou CSS
     * @param $entrada String com o conteúdo do arquivo
     * @param $nivel Nivel de compactação (0 - nenhum, 1 - minima, 2 - média, 3 - agressiva)
     * @param $cabecalho Texto que vai ser gerado no topo do arquivo final
     */
    public function minimizar($entrada, $nivel = false, $cabecalho = "") {
        // Se não foi determinado nenhum nível
        if ($nivel === false) {
            $dados = \controlador\Facil::$dadosIni;
            $saida = \JSMin::minify($entrada, $dados['saida']['reduzir_scripts'], $cabecalho);
        } else {
            $saida = \JSMin::minify($entrada, $nivel, $cabecalho);
        }
        return $saida;
    }

    /**
     * Método de acesso ao Singleton MnifyJsPlugin
     * @return MinifyJsPlugin
     */
    public static function getInstance() {
        if (empty(self::$instance)) {
            self::$instance = new MinifyJsPlugin();
        }
        return self::$instance;
    }

    public function __destruct() {
        unset($this->jsmin);
    }

}
?>