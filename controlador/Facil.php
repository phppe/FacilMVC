<?php

namespace controlador;


/**
 * Controlador geral do framework.
 *
 * Utilizaremos a seguinte regra de navegação:
 *
 * http://www.meusite.com.br/                      namespace: controlador,         classe: Home,   método: index
 * http://www.meusite.com.br/suddir/home/index     namespace: controlador\subdir,  classe: Home,   método: index
 * http://www.meusite.com.br/suddir/home/index/10  namespace: controlador\subdir,  classe: Home,   método: index,  param: 10
 * http://www.meusite.com.br/home/index            namespace: controlador,         classe: Home,   método: index
 * http://www.meusite.com.br/outra                 namespace: controlador,         classe: Outra,  método: index
 * http://www.meusite.com.br/outro                 namespace: controlador,         classe: Home,   método: outro
 * http://www.meusite.com.br/home/index/10         namespace: controlador,         classe: Home,   método: index,  param: 10
 * http://www.meusite.com.br/outra/10              namespace: controlador,         classe: Outra,  método: index,  param: 10
 * http://www.meusite.com.br/outro/10              namespace: controlador,         classe: Home,   método: outro,  param: 10
 *
 */
class Facil {

    /**
     * @var String
     */
    public static $dadosUrl;
    /**
     * @var String
     */
    public static $dadosIni;
    /**
     * @var String
     */
    public static $ambiente;
    /**
     * @var String
     */
    public static $namespace = "controlador";
    /**
     * @var String
     */
    public static $modulo;
    /**
     * @var String
     */
    public static $acao;
    /**
     * @var array
     */
    public static $parametros;
    /**
     * @var Modulo
     */
    public static $objeto;
    /**
     * @var ReflectionMethod
     */
    public static $metodo;
    /**
     * Idioma definido pelo usuário ou capturado através da requisição
     *
     * @var string
     */
    private static $idioma;
    /**
     * @var String
     */
    public static $template;
    /**
     * Coleção de variáveis registradas para a template
     * @var array
     */
    private static $variaveis = array();

    //  -------------  Métodos públicos -------------------

    /**
     *  Método para checar se alguma URL for informada
     *
     *  Aqui foi aplicado o padrão de refactoring Extract Method
     */
    public static function configurarAmbiente() {
        self::normalizarChamadasEmLinhaDeComando();
        self::recuperarAmbiente();
        self::registrarArquivoIni();
        self::definirReportesDeErro();
        self::definirRegionalizacao();
        self::verificarSeArquivoEstatico();
    }

    /**
     * Método que dá andamento ao fluxo da requisição
     */
    public static function invocarModulo() {
        self::registrarModuloEAcao();
        self::recuperarModulo();
        self::recuperarAcao();
        self::checarParametros();
        self::enviarCabecalhosGerais();
        self::invocarMetodo();
        self::exibirConsumoDeRecursos();
    }

    /**
     * Método utilitário para despachar para alguma view
     *
     * @var $_view Nome da tela que será chamada pelo Facil.
     * Não é necessário fornecer a extensão do arquivo
     * @var $retornar boolean Se true retorna em vez de imprimir na saída
     *
     */
    public static function despachar($_view , $retornar = false) {
        // Se não for fornecida a extensão, sugerir a padrão
        if (strpos($_view, ".") === false) {
            $_view = $_view . self::$dadosIni['extensao_padrao'];
        }
        
        // Verificando se a $view existe
        $incluir = VISAO . DS . self::getTemplate() . DS . $_view;
        if (!file_exists($incluir)) {
            throw new ControleException(ControleException::VISAO_INEXISTENTE, $_view);
        }

        // Carregando as variáveis
        foreach (self::$variaveis as $chave => $valor) {
            $$chave = $valor;
        }

        // Recuperando a view
        ob_start();
        include $incluir;
        $saida = ob_get_contents();
        ob_end_clean();

        $saida = self::resolverLinksDoControle($saida);
        $saida = self::resolverReferenciasARecursos($saida);
        $saida = self::internacionalizar($saida);

        if ($retornar) {
            return $saida;
        } else {
            echo $saida;
        }
    }
    
    private static function enviarCabecalhosGerais() {
        // Útil para o Explorer procurar se comportar como na 
        // versão mais nova ou carregar o modo Chrome Frame
        header("X-UA-Compatible: IE=Edge,chrome=1");
    }

    /**
     * Método para redirecionar para outras áreas do site ou ambientes externos
     *
     * @param string $_acao URL a redirecionar
     */
    public static function redirecionar($_acao, $codigo = 302) {
        if ($codigo == 301) {
            header("HTTP/1.1 301 Moved Permanently");
        }
        if (preg_match('|^\w{1,5}://|i', $_acao)) {
            header("Location: $_acao");
            exit();
        } else if (substr($_acao, 0, 1) == "/") {
            $_acao = substr($_acao, 1);
        }
        header("Location: " . \BASE_DINAMICA . "/$_acao" );
        exit();
    }

    /**
     * Método de processamento de erros HTTP.
     * Tratando os erros 403, 404 e 500.
     * 
     */
    public static function despacharErro($codigoHTTP, $depuracao = "") {
        switch($codigoHTTP) {
            case 403:
                $texto = "Forbidden";
                break;
            case 404:
                $texto = "Not Found";
                break;
            case 500:
                $texto = "Internal Server Error";
                break;
            default:
                $texto = "Erro";
        }
        header("HTTP/1.1 $codigoHTTP $texto");
        self::setar('codigo', $codigoHTTP);
        self::setar('texto', $texto);
        self::setar('depuracao', $depuracao);
        self::despachar("controle.html");
    }
    

    /**
     * Método que recupera a template escolhida no momento.
     * Baseia-se em uma possível template sugerida, caso 
     * contrário recupera da template padrão
     * @return string
     */
    public static function getTemplate() {
        if (isset(self::$template)) {
            return self::$template;
        } else {
            return self::$dadosIni['template_padrao'];
        }
    }

    /**
     * Método para definir uma template para sobrescrever a default
     *
     * @param string $template
     */
    public static function setTemplate($template) {
        self::$template = $template;
    }

    /**
     * Método que recupera o idioma definido pelo aplicativo,
     * um array com os idiomas colhidos a partir do Accept-Language
     * do browser ou string vazia caso não seja possível achar
     * nenhum dos dois.
     * @return string | array
     */
    public static function getIdioma() {
        if (isset(self::$idioma)) {
            return self::$idioma;
        } else {
            $locales = explode(",", preg_replace("|;q=[0-9.]{1,4}|", "", $_SERVER["HTTP_ACCEPT_LANGUAGE"]));
            if (is_array($locales) && count($locales) > 0) {
                return self::$idioma = $locales;
            } else {
                // Não foi possível recuperar o locale
                // a partir do Accept-Language do browser
                return "";
            }
        }
    }

    /**
     * Método para definir um Locale para o controlador buscar
     * arquivos MessageBundles referente ao idioma do site
     *
     * @param string $locale
     */
    public static function setIdioma($locale) {
        self::$idioma = $locale;
    }

    /**
     * Método para popular o array de variáveis que serão lidas no despachar
     * @param $chave Nome da variável lá no despachar
     * @param $valor Valor definido pelo usuário
     */
    public static function setar($chave, $valor) {
        self::$variaveis[$chave] = $valor;
    }

    /**
     * Método para buscar dados no escopo flash (até a próxima chamada da session)
     * @param string $chave
     */
    public static function getFlash($chave) {
        if (!session_id ()) {
            session_start();
        }
        if (isset($_SESSION['_flash'][$chave])) {
            $retorno = $_SESSION['_flash'][$chave];
            unset($_SESSION['_flash'][$chave]);
            return $retorno;
        }
    }

    /**
     * Método para registrar uma informação até a próxima chamada.
     * Escopo Flash
     *
     * @param <type> $chave
     * @param <type> $valor
     */
    public static function setFlash($chave, $valor) {
        if (!session_id ()) {
            session_start();
        }
        $_SESSION['_flash'][$chave] = $valor;
    }

    public static function resolverLinksDoControle($entrada) {
        $path = BASE_DINAMICA . "/";
        
        // Varrendo todas as classes Modulo
        $modulos = array();
        $di = new \DirectoryIterator(\CONTROLADOR);
        foreach ($di as $arq) {
            if ($arq->isDir() || $arq->isDot() ||
                    $arq->getFilename() == "Facil.php" ||
                    $arq->getFilename() == "ControleException.php")
                continue;
            $nome = explode(".", $arq->getFilename());
            $modulos[] = $nome[0];
        }

        // Varrendo todas as operações do módulo atual
        $classe = new \ReflectionClass("controlador\\" . self::$dadosIni['modulo_padrao']);

        $metodos = array();

        foreach ($classe->getMethods() as $metodo) {
            if ($metodo->isPublic() && strpos($metodo->getName(), "__") !== 0) {
                $metodos[] = strtolower($metodo->getName());
            }
        }
        $substituicoes = array_merge($modulos, $metodos);
        // Inserindo o caminho em BASE_DINAMICA para todos os links do arquivo HTML recuperado 
        $saida = preg_replace("@(href|action)\\s*=\\s*(['\"]?)(" . implode("|", $substituicoes) . ")([/ '\">])@i",
                        "\\1=\\2$path\\3\\4", $entrada);
        return $saida;
    }
    
    public static function resolverReferenciasARecursos($saida) {
        $ret = preg_match_all('@((href\s*=\s*)|(action\s*=\s*)|(src\s*=\s*)|(url\s*\(\s*))([\'"]?)' . // abertura da instrução
                              '((img/)|(css/)|(js/)|(recursos/))(.*?)([/ \'">])@iu', // seguida pelo conteúdo do recurso 
                              $saida, $ocorrencias, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $adendo = BASE_DINAMICA . DS . substr(VISAO, strrpos(VISAO, '/')+1) . DS . self::getTemplate() . DS;
        $desvio = 0;
        foreach ($ocorrencias as $ocorrencia) {
            $match = $ocorrencia[7][0];
            $posicao = $ocorrencia[7][1];
            $valor = $adendo . $match;
            $saida = substr_replace($saida, $valor, $posicao + $desvio, strlen($match));
            $desvio += strlen($valor) - strlen($match);
        }
        return $saida;
    }

    /**
     * Apenas uma rotina final de cálculo de consumo de recursos
     */
    public static function exibirConsumoDeRecursos() {
        // Ao final imprimir o tempo de execução
        // De acordo com o especificado no ini
        if (self::$dadosIni["saida"]["tempo"] == true) {
            echo " Tempo: " . (microtime(true) - INICIO) . "ms \n";
        }
        if (self::$dadosIni["saida"]["uso_de_memoria"] == true) {
            $memoria = memory_get_usage(true);
            if (self::$dadosIni["saida"]["uso_de_memoria"]) {
                echo " Memória alocada: ";
                $gb = 1024 * 1024 * 1024;
                $mb = 1024 * 1024;
                $kb = 1024;
                if ($memoria > $gb) {
                    $exibir = floor($memoria / $gb);
                    echo $exibir . "Gb ";
                    $memoria = $memoria - ($exibir * $gb);
                }
                
                if ($memoria > $mb) {
                    $exibir = floor($memoria / $mb);
                    echo $exibir . "Mb ";
                    $memoria = $memoria - ($exibir * $mb);
                }
                
                if ($memoria > $kb) {
                    $exibir = floor($memoria / $kb);
                    echo $exibir . "Kb ";
                    $memoria = $memoria - ($exibir * $kb);
                }
                if ($memoria) echo $memoria . "bytes\n";
            }
        }
    }

    // ----------------  Metodos privados (lógica interna) ----------

    private static function normalizarChamadasEmLinhaDeComando() {
        // Rotina para capturar parametros via CLI
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            // Exemplo de chamada em linha de comando:
            // php /var/www/phpexpert/projeto_php53/index.php principal/index
            // $_SERVER['argv'][0] :=> /var/www/phpexpert/projeto_php53/index.php
            // $_SERVER['argv'][1] :=> principal/index
            $_GET['_url'] = $_SERVER['argv'][1];
        }
    }
    
    private static function recuperarAmbiente() {
        if (isset($_GET['_url'])) {
            $url = $_GET['_url'];
        } else if (isset($_GET['_combo'])) {
            $url = $_GET['_combo'];
        } else if (isset($_GET['_combodir'])) {
            $url = $_GET['_combodir'];
        }
        // Captura dados ini padrão
        self::$dadosIni = parse_ini_file(CONFIG . DS . 'padrao.ini', true);            
        if (!empty($url)) {
            // Captura dos dados passados na URL
            self::$dadosUrl = explode("/", $url);
            
            // Se for possível sugerir o ambiente
            // e houver arquivo de configuração para o ambiente sugerido
            if (self::$dadosIni['sugerir_ambiente'] &&
                    isset(self::$dadosUrl[0]) &&
                    self::$dadosUrl[0] != "padrao" &&
                    file_exists(CONFIG . DS . self::$dadosUrl[0] . ".ini")) {
                // Retira o primeiro elemento do array para compor
                // a informação do ambiente sugerido
                // a url é uma fila, array_shift é quem consome a fila (retira e retorna o primeiro elemento)
                self::$ambiente = array_shift(self::$dadosUrl);
                return;
            }
        }
        self::$ambiente = self::$dadosIni['ambiente_padrao'];
    }
    
    private static function registrarArquivoIni($novoIni = "") {
        if (empty($novoIni)) {
            $ini = CONFIG . DS . self::$ambiente . '.ini';
        } else {
            $ini = CONFIG . DS . $novoIni . '.ini';
        }
        $novosDadosIni = parse_ini_file($ini, true);
        // Registrando o array de dados colhido do ambiente
        // combinado com o valor que self::$dadosIni já tinha
        // No caso de índices iguais, o segundo prevalece
        self::$dadosIni = array_merge($novosDadosIni, self::$dadosIni);
        if ($novosDadosIni['controle']['extends'] != "padrao") {
            self::registrarArquivoIni($novosDadosIni['controle']['extends']);
            self::$dadosIni['controle']['extends'] = $novosDadosIni['controle']['extends'];
        }
    }

    /**
     * Método que define o comportamento do servidor no caso de erros e exceções
     * @throws Exception
     */
    private static function definirReportesDeErro() {
        // Varrendo as diretivas de erro do arquivo de ini do ambiente escolhido
        foreach (self::$dadosIni['erros'] as $diretiva => $valor) {
            if ($diretiva == 'error_log') {
                ini_set($diretiva, TMP . DS . $valor);
            } else {
                ini_set($diretiva, $valor);
            }
        }
        // Definindo o método cata-tudo de exceções não capturadas
        set_exception_handler(array('controlador\ControleException', 'capturar'));
    }

    /**
     * Método para definir características regionais.
     * Como: locale, timezone e charset
     */
    private static function definirRegionalizacao() {
        // Definição da regionalização
        $regioes = preg_split("|, ?|", self::$dadosIni['l10n']['regiao']);
        setlocale(LC_ALL, $regioes);

        // Definição do fuso horário
//        date_default_timezone_set(self::$dadosIni['l10n']['timezone']);
        ini_set('date.timezone', self::$dadosIni['l10n']['timezone']);

        // Definição do charset
        header("Content-type: text/html; charset=" . self::$dadosIni['l10n']['charset']);
    }
    
    public static function verificarSeArquivoEstatico() {
        // Recuperar variável _combo que traz o nome do arquivo
        // que serve de combo de arquivos Javascript e CSS
        if (!empty($_GET['_combo'])) {
            $combo = RAIZ . DS . preg_replace("|^" . self::$ambiente . "/|i", "/", $_GET['_combo']);
            self::cabecalhosDeArquivosEstaticos($combo);
            if (file_exists($combo)) {
                $arqs = file($combo, FILE_IGNORE_NEW_LINES);
                $dir = dirname($combo) . DS;
                ob_start();
                $arquivoMaisNovo = filemtime($combo);
                foreach ($arqs as $arq) {
                    // Simplesmente adiciona se o arquivo 
                    // existir e ignora se não existir
                    if (file_exists($dir . $arq)) {
                        include $dir . $arq;
                        if (filemtime($dir . $arq) > $arquivoMaisNovo) {
                            $arquivoMaisNovo = filemtime($dir . $arq);
                        }
                    }
                    echo PHP_EOL;
                }
                self::responderArquivosEstaticos($arquivoMaisNovo);
            } else {
                exit();
            }
        }
        
        if (!empty($_GET['_combodir'])) {
            $combodir = RAIZ . DS . preg_replace("|^" . self::$ambiente . "/|i", "/", $_GET['_combodir']);
            self::cabecalhosDeArquivosEstaticos($combodir);
            if (file_exists(dirname($combodir))) {
                $di = new \DirectoryIterator(dirname($combodir));
                ob_start();
                $arquivoMaisNovo = 0;
                foreach ($di as $arq) {
                    if ($arq->isFile() && $arq->isReadable()) {
                        include $arq->getPathname();
                        if (filemtime($arq->getPathname()) > $arquivoMaisNovo) {
                            $arquivoMaisNovo = filemtime($arq->getPathname());
                        }
                    }
                }
                self::responderArquivosEstaticos($arquivoMaisNovo);
            } else {
                exit();
            }
        }
        
        // Verificando se houve uma chamada direta a arquivo estático que
        // acabou caindo aqui por estar prefixada com o nome do ambiente
        // ou foi deliberadamente mapeada no .htaccess
        if (!empty($_GET['_url'])) {
            $url = RAIZ . DS . preg_replace("|^" . self::$ambiente . "/|i", "/", $_GET['_url']);
            if (file_exists($url)) {
                self::cabecalhosDeArquivosEstaticos($url);
                ob_start();
                include $url;
                self::responderArquivosEstaticos(filemtime($url));
            }
        }
    }
    /**
     * Método interno para enviar cabeçalhos básicos de arquivos estáticos
     * como JS e CSS manipulados em PHP por combos e diretórios.
     * Envia Content-type, Last-Modified e retorna If-Modified-Since para
     * controle de uso do cache
     * @param $caminho Caminho absoluto até o combo solicitado
     */
    private static function cabecalhosDeArquivosEstaticos($caminho) {
        header("Last-modified: " . gmdate('r'));

        if (substr($caminho, -4) == ".css") {
            header("Content-type: text/css");
        } else if (substr($caminho, -3) == ".js") {
            header("Content-type: application/javascript");
        }
    }
    
    /**
     * Método para retornar a resposta ao cliente em 
     * solicitações de arquivos estáticos como JS e CSS.
     * Retorna 304 se não foi modificado ou a resposta 200 padrão
     * @param type $arquivoMaisNovo timestamp do arquivo mais novo
     */
    private static function responderArquivosEstaticos($arquivoMaisNovo) {
        ini_set('date.timezone', 'America/Recife');
        if (!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
            $if_modified_since = strtotime(preg_replace('/;.*$/','',$_SERVER["HTTP_IF_MODIFIED_SINCE"]));
        } else {
            $if_modified_since = 0;
        }
        if ($arquivoMaisNovo < $if_modified_since) {
            header("HTTP/1.1 304 Not Modified");
            ob_end_clean();
        } else {
            $saida = ob_get_contents();
            ob_end_clean();
            $saida = self::internacionalizar($saida);
            echo (self::$dadosIni['saida']['reduzir_scripts']) ? self::reduzirConteudo($saida) : $saida;
        }
        exit();
    }
    
    private static function reduzirConteudo($entrada) {
        $saida = "";
        $linhas = explode(PHP_EOL, $entrada);
        $pular = 0;
        foreach ($linhas as $linha) {
            // Retirando espaços em branco no início e no fim da linha
            $linha = trim($linha);
            // Ignorando linhas vazias
            if (!empty ($linha)) {
                // Verificando se há uma sequencia " // " na linha
                $posBarraBarra = strpos($linha, "//");
                if ($posBarraBarra !== false) {
                    $linha = substr($linha, 0, $posBarraBarra);
                }
                // Verificando blocos de comentário " /* "
                $posBarraAsterisco = strpos($linha, "/*");
                $posAsteriscoBarra = strpos($linha, "*/");
                if ($posBarraAsterisco !== false) {
                    $linha = substr($linha, 0, $posBarraAsterisco);
                    $pular++;
                }
                if ($posAsteriscoBarra !== false) {
                    $linha = substr($linha, $posAsteriscoBarra + 2);
                    $pular--;
                }
                // Só incluir linhas que não estejam em blocos de comentários
                if (!$pular) {
                    $pattern = '@(\s*)([]"\'\(),;+*/%[{}=-])(\s*)@';
                    // Não reduzir trechos entre aspas ou barras 
                    // (conteúdo de strings e expressões regulares)
                    $ret = preg_match_all('|([/"\'])|u', $linha, $aspas, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
                    if ($ret) {
                        $posicao1 = $aspas[0][0][1];
                        $posicaoN = $aspas[count($aspas)-1][0][1];
                        $saida .= preg_replace($pattern, '\2', substr($linha, 0, $posicao1)) . 
                                  substr($linha, $posicao1, $posicaoN - $posicao1) . 
                                  preg_replace($pattern, '\2', substr($linha, $posicaoN));
                    } else {
                        $saida .= preg_replace($pattern, '\2', $linha);
                    }
                    
                }
            }
        }
        return $saida;
    }
    

    /**
     * Método para buscar na URL a classe e o método que vamos instanciar e executar, respectivamente
     */
    private static function registrarModuloEAcao() {
    	// retirando a última posição de $dadosUrl se ela for string vazia
        if (!empty(self::$dadosUrl) && empty(self::$dadosUrl[count(self::$dadosUrl)-1])) {
            array_pop(self::$dadosUrl);
        }
        
        // Se ainda houver registros na URL - retirar módulo
    	if (count(self::$dadosUrl) > 0) {
            // Retirar a informação do módulo
            self::$modulo = array_shift(self::$dadosUrl);
            // Se ainda houver registros na URL - retirar ação
            if (count(self::$dadosUrl) > 0) {
                self::$acao = array_shift(self::$dadosUrl);
            // Se não houver mais
            } else {
                // Apenas ação determinada a partir
                // do padrão para aquele ambiente
                self::$acao = self::$dadosIni['acao_padrao'];
            }
        // Se não houver mais
        } else {
            // Módulo e ação determinados a partir
            // do padrão para aquele ambiente
            self::$modulo = self::$dadosIni['modulo_padrao'];
            self::$acao = self::$dadosIni['acao_padrao'];
        }
    }

    /**
     * Método que busca a classe do módulo registrado
     */
    private static function recuperarModulo() {
    	// Se o que chamamos de módulo for um diretório existente no controlador
    	if (is_dir(RAIZ . DS . self::$namespace . DS . self::$modulo)) {
    		// Dar um shift pra direita nos nomes dos elementos
    		// Quem é primeiro parâmetro vira metodo, que é método vira parametro
    		self::$namespace .= "\\" . self::$modulo;
    		self::$modulo = self::$acao;
			if (count(self::$dadosUrl) > 0) {
				self::$acao = array_shift(self::$dadosUrl);
			} else {
				self::$acao = self::$dadosIni['acao_padrao'];
			}
    	}
        // Se a classe não existe, verificar se a chamada é para o método direto
    	if (!class_exists(self::$namespace . "\\" . ucfirst(self::$modulo))) {
    		// Recuperando os parâmetros passados
    		if ((self::$acao != self::$dadosIni['acao_padrao']) && (self::$acao != self::$modulo)) {
    			array_unshift(self::$dadosUrl, self::$acao);
    		}

    		self::$acao = self::$modulo;
    		self::$modulo = self::$dadosIni['modulo_padrao'];
    	}

    	// Se agora a classe passou a existir
    	if (class_exists(self::$namespace . "\\" . ucfirst(self::$modulo))) {
    		// Cria o objeto
	    	$obj = self::$namespace . "\\" . ucfirst(self::$modulo);
			self::$objeto = new $obj();
    	} else {
            throw new ControleException(ControleException::MODULO_INEXISTENTE,
            							self::$namespace . "\\" . ucfirst(self::$modulo));

    	}
    }

    /**
     * Método que vai usar a API de Reflection para
     * buscar o método da classe que precisamos executar.
     *
     * Vai armazenar em self::$metodo ou levantar um ControleException
     *
     * @param string $modulo Nome da classe modulo. Opcional
     * @throws ControleException
     * @version 1.1 Adicionada a chamada __call() caso a classe tenha
     */
    private static function recuperarAcao() {
        try {
	    	$rc = new \ReflectionClass(self::$namespace . "\\" . ucfirst(self::$modulo));
	        // Se não existir, a linha abaixo levantará
	        // uma ReflectionException
	        $metodo = $rc->getMethod(self::$acao);
	        // Já que passou $metodo é um ReflectionMethod
	        // Verificando se o método é visível
	        if ($metodo->isPublic()) {
	            self::$metodo = $metodo;
                // Se não for vísivel, lenvantar ControleException
	        } else {
	            throw new
	            ControleException(ControleException::ACAO_PROTEGIDA,
	                    self::$namespace . "\\" . ucfirst(self::$modulo) . "::" . self::$acao);
	        }

        } catch (\ReflectionException $ex) {
            try {
                self::$metodo = $rc->getMethod('__call');
            } catch (\ReflectionException $ex) {
                throw new ControleException(ControleException::ACAO_INEXISTENTE,
                        self::$namespace . "\\" . ucfirst(self::$modulo) . "::" . self::$acao);
            }
        }
    }

    /**
     * Método para verificar se os parâmetros informados são pelo menos o total
     * exigido na classe do módulo.
     *
     * @throws ControleException
     * @version 1.1 Adicionada a chamada __call() caso a classe tenha
     */
    private static function checarParametros() {
        // Se o total de parâmetros não suprir o número requerido
        if (self::$metodo->getName() != '__call' &&
            count(self::$dadosUrl) <
                self::$metodo->getNumberOfRequiredParameters()) {
            throw new ControleException(
                    ControleException::PARAMETROS_INSUFICIENTES,
                    ucfirst(self::$modulo) . "::" . self::$acao);
        }
    }

    /**
     * Método onde a coisa acontece.
     * Chama o método da classe recuperada
     * @version 1.1 Adicionada a chamada __call() caso a classe tenha
     */
    private static function invocarMetodo() {
        if (self::$metodo->getName() == '__call') {
            self::$metodo->invokeArgs(self::$objeto, array(self::$acao, self::$dadosUrl));
        } else {
            self::$metodo->invokeArgs(self::$objeto, self::$dadosUrl ? : array());
        }
        
    }

    private static function internacionalizar($saida) {
        // Aplicando internacionalização
        if (self::$dadosIni['l10n']['i18n']) {
            // casa com $principal.logon.esqueceu case-insensitive, em qualquer linha (im)
            // organiza por ocorrência (PREG_SET_ORDER) e recupera também a posição da ocorrência (PREG_OFFSET_CAPTURE)
            $ret = preg_match_all('|\{\$(\w+)\.(\w+)\.?(\w+)?\}|iu', $saida, $ocorrencias, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            $mensagens = array();
            $desvio = 0;
            foreach ($ocorrencias as $ocorrencia) {
                $match = $ocorrencia[0][0];
                $posicao = $ocorrencia[0][1];
                $ini = self::obterArquivoDeIdioma($ocorrencia[1][0]);
                if (!empty($ini)) {
                    if (empty($mensagens[$ini])) {
                        $mensagens[$ini] = parse_ini_file(CONFIG . DS . 'i18n' . DS . $ini, true);
                    }
                    // Se NÃO houver divisão das mensagens em categorias no arquivo .ini
                    if (count($ocorrencia) == 3) {
                        $mensagem = @$mensagens[$ini][$ocorrencia[2][0]];
                        // Se houver divisão das mensagens em categorias no arquivo .ini
                    } else if (count($ocorrencia) == 4) {
                        $mensagem = @$mensagens[$ini][$ocorrencia[2][0]][$ocorrencia[3][0]];
                    }
                    if (isset($mensagem)) {
                        $saida = substr_replace($saida, $mensagem, $posicao + $desvio, strlen($match));
                        $desvio += strlen($mensagem) - strlen($match);
                    }
                }
            }
        }
        return $saida;
    }

    private static function obterArquivoDeIdioma($nomeBase) {
        $idioma = self::getIdioma();
        // Se não houver idioma definido
        if (empty($idioma)) {
            if (file_exists(CONFIG . DS . 'i18n' . DS . "$nomeBase.ini")) {
                return "$nomeBase.ini";
            }
            // Caso o idioma seja uma string (geralmente por já ter sido definido pelo aplicativo)
        } else if (is_string($idioma)) {
            $nome = $nomeBase . "_" . str_replace("-", "_", strtolower($idioma)) . ".ini";
            if (file_exists(CONFIG . DS . 'i18n' . DS . $nome)) {
                return $nome;
            }
            // Caso o idioma seja um array (geralmente por ter sido localizado pelo Accept-Language)
        } else if (is_array($idioma)) {
            // Varrer os idiomas em busca de algum arquivo
            foreach ($idioma as $locale) {
                $arquivo = $nomeBase . "_" . str_replace("-", "_", strtolower($locale)) . ".ini";
                // Se algum arquivo for encontrado, retornar o nome dele
                if (file_exists(CONFIG . DS . 'i18n' . DS . $arquivo)) {
                    return $arquivo;
                }
            }
            // Se varrer tudo e o arquivo não for encontrado, retornar como se nenhum idioma
            // tivesse sido definido (já que eles são normalmente, capturados via Accept-Language)
            if (file_exists(CONFIG . DS . 'i18n' . DS . "$nomeBase.ini")) {
                return "$nomeBase.ini";
            }
        }
    }
}
?>
