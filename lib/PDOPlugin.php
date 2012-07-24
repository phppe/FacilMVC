<?php

namespace lib;

/**
 * Classe plugin para carregar o PDO
 */
class PDOPlugin implements IPlugin {

    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var PDOPlugin
     */
    private static $instance;

    private function __construct() {
        $this->carregar();
    }

    /**
     * Método para carregar o objeto PDO
     *
     */
    public function carregar() {
        $dados = \controlador\Controlador::getDadosIni();
        $this->pdo = new \PDO(sprintf('%s:host=%s;dbname=%s;port=%04d',
                                $dados['banco']['sgbd'],
                                $dados['banco']['host'],
                                $dados['banco']['database'],
                                $dados['banco']['porta']),
                        $dados['banco']['usuario'], $dados['banco']['senha']);
    }

    /**
     * Método de acesso ao Singleton PDOPlugin
     * @return PDOPlugin
     */
    public static function getInstance() {
        if (empty(self::$instance)) {
            self::$instance = new PDOPlugin();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function __destruct() {
        unset($this->pdo);
    }

}
?>