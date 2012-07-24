<?php
/**
 * Arquivo que define as configurações iniciais do sistema
 */

/**
 * Momento inicial da execução do script
 */
define('INICIO', microtime(true));
/**
 * Barras de acordo com o S.O.
 * Já estamos usando a declaração const fora de classes
 * no lugar do define. Note que a constante anterior,
 * por ter um valor fruto da execução de uma função
 * ainda não é válida via const
 */
const DS = DIRECTORY_SEPARATOR;
/**
 * Ponto e virgula ou dois pontos de acordo com o S.O.
 */
const PS = PATH_SEPARATOR;

// -------------------------
// Constantes dos diretórios da aplicação
// -------------------------

/**
 * Raiz física da aplicação
 */
define ('RAIZ', __DIR__); // Constante nova no PHP 5.3 (equivale a dirname(__FILE__) no <= 5.2)

/**
 * Raiz física da aplicação a partir do DOCUMENT_ROOT na URL
 *
 */
define('BASE', (dirname($_SERVER['PHP_SELF']) == "/" ?
                "" : dirname($_SERVER['PHP_SELF'])));


/**
 * Diretório das configurações
 */
define ('CONFIG', RAIZ . DS . "config");

/**
 * Diretório principal da camada de controle
 */
define ('CONTROLADOR', RAIZ . DS . "controlador");

/**
 * Diretório reservado a bibliotecas de terceiros.
 * Ex.: Doctrine
 */
define ('LIB', RAIZ . DS . "lib");

/**
 * Diretório onde salvar os arquivos de cada modelo de negócio
 */
define ('MODELO', RAIZ . DS . "modelo");

/**
 * Diretório de logs e arquivos dinâmicos ou temporários
 */
define ('TMP', RAIZ . DS . "tmp");

/**
 * Diretório dos arquivos da camada da visão.
 * Templates e helpers
 */
define ('VISAO', RAIZ . DS . "visao");

// --------------------------------------------

/**
 * Arquivo de configuração da classe que carregará as demais
 */
include CONFIG . DS . 'Autoload.php';

/**
 * Classe controladora geral do framework
 */
use controlador\Facil;

// Configurando o ambiente de execução
Facil::configurarAmbiente();


/**
 * Base da aplicação com resolução de ambiente
 * @see BASE
 */
define('BASE_DINAMICA', BASE .
    ((Facil::$ambiente != Facil::$dadosIni['ambiente_padrao']) ?
    ("/" . Facil::$ambiente) : "")
);

// Despachando a requisição para o controlador
Facil::invocarModulo();

?>