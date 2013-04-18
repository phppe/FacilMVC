<?php
namespace lib;

use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    \Doctrine\ORM\Tools\SchemaValidator,
    \Doctrine\ORM\Tools\SchemaTool;



/**
 * Classe plugin para carregar o Doctrine.
 * Age também como uma Fachada para operações do Doctrine
 * como a geração e verificação do esquema no banco
 */
class DoctrinePlugin implements IPlugin {
    
    /**
     *
     * @var DoctrinePlugin
     */
    private static $instance;
    
    /**
     *
     * @var \PDO
     */
    private $pdo;

    /**
     * @var Configuration
     */
    private $config;

    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;
    
    /**
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private $sm;
    
    /**
     *
     * @var EntityManager
     */
    private $em;
    
    /**
     * Construtor que carrega dados do INI
     * @param $pdo PDO Objeto que representa a conexão com o banco
     */
    private function __construct($pdo) {
        $this->pdo = $pdo;
        $this->carregar($pdo);
    }
    
    private function obterLoaders($dados) {
        require_once LIB . DS . 'Doctrine/Common/ClassLoader.php';

        $doctrineClassLoader = new ClassLoader('Doctrine',  LIB);
        $doctrineClassLoader->register();
        $entitiesClassLoader = new ClassLoader('modelo', MODELO);
        $entitiesClassLoader->register();
        $proxiesClassLoader = new ClassLoader($dados['proxy_namespace'], RAIZ . DS . $dados['proxy_dir']);
        $proxiesClassLoader->register();
    }
    
    private function obterConexao() {
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection(array('pdo' => $this->pdo));
        $this->sm = $this->connection->getSchemaManager();
    }
    
    private function obterConfiguracao($dados) {
        $this->config = new Configuration();
        
        // Definição do driver de mapeamento
        $metadata_driver = "Doctrine\ORM\Mapping\Driver\\$dados[metadata_driver]";
        if ($dados['metadata_driver'] == "AnnotationDriver") {
            $driver = $this->config->newDefaultAnnotationDriver(array(MODELO), /* $useSimpleAnnotationReader */ true);
        } else {
            $driver = new $metadata_driver(CONFIG . DS . 'orm');
        }
        if (!empty($dados['map_paths'])) {
            $driver->addPaths(preg_split('/, ?/', $dados['map_paths']));
        }
        $this->config->setMetadataDriverImpl($driver);
        
        // Configurações de proxies
        $this->config->setProxyDir(RAIZ . DS . $dados['proxy_dir']);
        $this->config->setProxyNamespace($dados['proxy_namespace']);
        $this->config->setAutoGenerateProxyClasses($dados['auto_proxies']);

        // Definição da estratégia de caches de consultas e metadados
        $metadata_cache = "Doctrine\Common\Cache\\$dados[metadata_cache]";
        $query_cache    = "Doctrine\Common\Cache\\$dados[query_cache]";
        $result_cache   = "Doctrine\Common\Cache\\$dados[query_cache]";
        $this->config->setMetadataCacheImpl(new $metadata_cache());
        $this->config->setQueryCacheImpl(new $query_cache());
        $this->config->setResultCacheImpl(new $result_cache());
        
        // Ferramenta de log de consultas
        if (!empty($dados['sql_logger'])) {
            $sql_logger = "Doctrine\DBAL\Logging\\$dados[sql_logger]";
            $logger = new $sql_logger();
            $this->config->setSQLLogger($logger);
        }        
    }

    public function carregar() {
        $dados = \controlador\Facil::$dadosIni['doctrine'];
        
        $this->obterLoaders($dados);
        $this->obterConfiguracao($dados);
        $this->obterConexao();

        // Create EntityManager
        $this->em = EntityManager::create($this->connection, $this->config);
        if (\controlador\Facil::$dadosIni['l10n']['charset'] == 'UTF-8') {
            $this->em->getEventManager()->addEventSubscriber(
                    new \Doctrine\DBAL\Event\Listeners\MysqlSessionInit('utf8', 'utf8_unicode_ci')
            );
        }
    }
    
    /**
     * Método que valida se os mapeamentos estão coerentes
     * com a relação Classes x Tabelas por todo o esquema
     * @return boolean
     */
    public function validarEsquema() {
        $sv = new SchemaValidator($this->em);
        return $sv->validateMapping();
    }
    /**
     * Método que valida se o mapeamento de uma classe específica
     * está coerente com a sua tabela correspondente
     * @param string $nome FQCN da classe
     * @return boolean
     */
    public function validarClasse($nome) {
        $sv = new SchemaValidator($this->em);
        return $sv->validateClass($this->em->getClassMetadata($nome));
    }
    
    /**
     * Método que gera o esquema no banco a partir da lista de classes
     * @param string[] $classes FQN das classes
     */
    public function gerarEsquema($classes) {
        $st = $this->getSchemaTool();
        $metas = $this->montarMetadatas($classes);
        $st->createSchema($metas);
    }
    
    /**
     * Método que detona todo o esquema gerado a partir das classes informadas
     * @param string[] $classes FQN das classes
     */
    public function dropEsquema($classes) {
        $st = $this->getSchemaTool();
        $metas = $this->montarMetadatas($classes);
        $st->dropSchema($metas);
    }
    
    /**
     * Método interno usado pelo gerarEsquema() e dropEsquema()
     * @param string[] $classes
     * @return \Doctrine\ORM\Mapping\ClassMetadata[]
     */
    private function montarMetadatas($classes) {
        $metas = array();
        foreach ($classes as $c) {
            $metas[] = $this->em->getClassMetadata($c);
        }
        return $metas;
    }
    
    public function getSchemaTool() {
        return new SchemaTool($this->em);
    }
    
    /**
     * 
     * @param PDO $pdo
     * @return DoctrinePlugin
     */
    public static function getInstance($pdo) {
        if (empty(self::$instance)) {
            self::$instance = new DoctrinePlugin($pdo);
        }
        return self::$instance;
    }
    
    public function __get($atr) {
        return $this->$atr;
    }
    
    public function __set($atr, $val) {
        throw new \controlador\ControleException(0 , "Atributo privado");
    }
    
}