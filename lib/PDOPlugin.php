<?php

namespace lib;

/**
 * Classe plugin para carregar o PDO
 */
class PDOPlugin implements IPlugin {
    
    /**
     * Constante que determina que a(s) conexão(ões) já será(ão) criada(s) ao carregar o plugin
     */
    const EAGER = 1;
    /**
     * Constante que determina que a(s) conexão(ões) só será(ão) criada(s) quando for necessário
     */
    const LAZY  = 2;

    /**
     * @var mixed Objeto PDO em ambientes com única conexão ou array com mais de uma
     */
    private $pdo;
    
    /**
     * @var int Método de carregamento de conexões
     */
    private static $modo;
    
    /**
     * @var PDOPlugin
     */
    private static $instance;

    private function __construct() {
        if (self::$modo == self::EAGER) {
            $this->carregar();
        } else {
            $this->preConfigurar();
        }
    }

    /**
     * Método para carregar o(s) objeto(s) PDO
     *
     */
    public function carregar() {
        $dados = \controlador\Facil::getDadosIni();
        // Se houver uma posição absoluta chamada banco no 
        // arquivo de configuração, só vamos conectar uma vez
        if (isset($dados['banco'])) {
            $this->pdo = new \PDO(sprintf('%s:host=%s;dbname=%s;port=%04d;charset=%s',
                                $dados['banco']['sgbd'],
                                $dados['banco']['host'],
                                $dados['banco']['database'],
                                $dados['banco']['porta'],
                                $dados['l10n']['charset']),
                                $dados['banco']['usuario'], $dados['banco']['senha']);
            $this->configurarObj($this->pdo);
        } else {
            $this->pdo = array();
            for ($x = 0; isset($dados['banco_' . $x]); $x++) {
                $this->pdo[$x] = new \PDO(sprintf('%s:host=%s;dbname=%s;port=%04d;charset=%s',
                                        $dados["banco_$x"]['sgbd'],
                                        $dados["banco_$x"]['host'],
                                        $dados["banco_$x"]['database'],
                                        $dados["banco_$x"]['porta'],
                                        $dados['l10n']['charset']),
                                        $dados["banco_$x"]['usuario'], $dados["banco_$x"]['senha']);
                $this->configurarObj($this->pdo[$x]);
            }
        }
    }
    
    public function configurarObj($obj) {
        $dados = \controlador\Facil::$dadosIni['pdo'];
        $obj->setAttribute(\PDO::ATTR_ERRMODE, constant('\PDO::' . $dados['ATTR_ERRMODE']));
        $obj->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, constant('\PDO::' . $dados['ATTR_DEFAULT_FETCH_MODE']));
        $obj->setAttribute(\PDO::ATTR_CASE, constant('\PDO::' . $dados['ATTR_CASE']));
        $obj->setAttribute(\PDO::ATTR_ORACLE_NULLS, constant('\PDO::' . $dados['ATTR_ORACLE_NULLS']));
        $obj->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, $dados['ATTR_STRINGIFY_FETCHES']);
        
        if (!empty($dados['ATTR_STATEMENT_CLASS'])) 
            $obj->setAttribute(\PDO::ATTR_STATEMENT_CLASS, $dados['ATTR_STATEMENT_CLASS']);
        if (!empty($dados['ATTR_TIMEOUT'])) 
            $obj->setAttribute(\PDO::ATTR_TIMEOUT, $dados['ATTR_TIMEOUT']);
        if (!empty($dados['ATTR_AUTOCOMMIT'])) 
            $obj->setAttribute(\PDO::ATTR_AUTOCOMMIT, $dados['ATTR_AUTOCOMMIT']);
        if (!empty($dados['ATTR_EMULATE_PREPARES'])) 
            $obj->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $dados['ATTR_EMULATE_PREPARES']);
        if (!empty($dados['MYSQL_ATTR_USE_BUFFERED_QUERY'])) 
            $obj->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $dados['MYSQL_ATTR_USE_BUFFERED_QUERY']);
    }
    
    /**
     * Método semelhante ao carregar, mas apenas pre-configura sem estabelecer a conexão.
     */
    public function preConfigurar() {
        $dados = \controlador\Facil::getDadosIni();
        // Se houver uma posição absoluta chamada banco no 
        // arquivo de configuração, só vamos conectar uma vez
        if (isset($dados['banco'])) {
            $this->pdo = new PDOPreConfig(
                            $dados['banco']['sgbd'],
                            $dados['banco']['host'],
                            $dados['banco']['database'],
                            $dados['banco']['porta'],
                            $dados['banco']['usuario'],
                            $dados['banco']['senha']
                        );
        } else {
            $this->pdo = array();
            for ($x = 0; isset($dados['banco_' . $x]); $x++) {
                $this->pdo[$x] = new PDOPreConfig(
                                        $dados["banco_$x"]['sgbd'],
                                        $dados["banco_$x"]['host'],
                                        $dados["banco_$x"]['database'],
                                        $dados["banco_$x"]['porta'],
                                        $dados["banco_$x"]['usuario'], 
                                        $dados["banco_$x"]['senha']);
            }
        }
    }

    /**
     * Método de acesso ao Singleton PDOPlugin.
     * @param int Modo de carregamento de conexões
     * @see self::EAGER
     * @see self::LAZY
     * @return PDOPlugin
     */
    public static function getInstance($modo = self::EAGER) {
        self::$modo = $modo;
        if (empty(self::$instance)) {
            self::$instance = new PDOPlugin();
        }
        return self::$instance;
    }

    /**
     * Objeto PDO referente à conexão solicitada
     * @return \PDO
     */
    public function getPdo($indice = 0) {
        // Se o atributo pdo for do tipo \PDO mesmo
        // Apenas retornar ele, não há nada a ser feito
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }
        
        // Se o atributo PDO for do tipo PDOPreConfig
        // significa que o modo é Lazy e só há uma conexão
        // Devemos então conectar e retornar o objeto PDO
        if ($this->pdo instanceof PDOPreConfig) {
            $this->pdo = new \PDO(sprintf('%s:host=%s;dbname=%s;port=%04d',
                            $this->pdo->sgbd,
                            $this->pdo->host,
                            $this->pdo->database,
                            $this->pdo->porta),
                            $this->pdo->usuario, $this->pdo->senha);
            return $this->pdo;
        
        } 
        
        // Se o atributo pdo for um array contendo objetos \PDO
        // Apenas retornar a posição solicitada desse array
        if (is_array($this->pdo) && $this->pdo[$indice] instanceof \PDO) {
            return $this->pdo[$indice];
        }
        
        // Se o atributo PDO for do tipo array
        // significa que há mais de uma conexão
        // Se a posição corrente do array for PDOPreConfig
        // significa que devemos conectar a partir dela
        if (is_array($this->pdo) && $this->pdo[$indice] instanceof PDOPreConfig) {
            $this->pdo[$indice] = new \PDO(sprintf('%s:host=%s;dbname=%s;port=%04d',
                                    $this->pdo[$indice]->sgbd,
                                    $this->pdo[$indice]->host,
                                    $this->pdo[$indice]->database,
                                    $this->pdo[$indice]->porta),
                                    $this->pdo[$indice]->usuario, $this->pdo[$indice]->senha);
            return $this->pdo[$indice];
        }
        
    }

    public function __destruct() {
        unset($this->pdo);
    }
}

/**
 * Classe de uso interno pelo plugin
 */
class PDOPreConfig {
    public $sgbd;
    public $host;
    public $database;
    public $porta;
    public $usuario;
    public $senha;
    
    public function __construct($sgbd, $host, $database, $porta, $usuario, $senha) {
        $this->sgbd     = $sgbd;
        $this->host     = $host;
        $this->database = $database;
        $this->porta    = $porta;
        $this->usuario  = $usuario;
        $this->senha    = $senha;
    }
}

?>