<?php
namespace controlador;

/**
 * Exceção geral de fluxo do controlador
 * Captura também qualquer exceção não capturada
 *
 * @package controlador
 */
class ControleException extends \Exception {
	/**#@+
	 * @var int
	 */
	const MODULO_INEXISTENTE        = 1;
	const ACAO_INEXISTENTE          = 2;
	const ACAO_PROTEGIDA            = 3;
	const PARAMETROS_INSUFICIENTES  = 4;
	const VISAO_INEXISTENTE         = 5;
	/**#@-*/
	const EXCECAO_NAO_CAPTURADA     = 6;

	/**
	 * Construtor da Exceção
	 *
	 * @param int $codigo
	 */
	public function __construct($codigo, $nome) {
		$mensagem = "";
		switch ($codigo) {
			case self::MODULO_INEXISTENTE:
				$mensagem = "A classe referente ao módulo " .
				 			"requisitado [$nome] não foi encontrada!";
				break;
			case self::ACAO_INEXISTENTE:
				$mensagem = "Ação [$nome] selecionada não foi declarada " .
                    		"no módulo!";
				break;
			case self::ACAO_PROTEGIDA:
				$mensagem = "Ação [$nome] de uso interno, não deve " .
                    		"ser chamada diretamente!";
				break;
			case self::PARAMETROS_INSUFICIENTES:
				$mensagem = "O número de parâmetros informados " .
		                    "na URL não é suficiente para " . 
		                    "executar esta ação [$nome]!";
				break;
			case self::VISAO_INEXISTENTE:
				$mensagem = "A template [$nome] da visão selecionada não foi " .
                    		"encontrada em " . VISAO . DS . Facil::getTemplate();
				break;
		}
		parent::__construct($mensagem, $codigo);
	}

	/**
	 * Método estático para capturar qualquer exceção
	 *
	 * @param Exception $excecao
	 */
	public static function capturar($excecao) {
            $capturar = $detalhar = $depurar = "";
            $dados_ambiente = Facil::$dadosIni;
            switch ($dados_ambiente['excecoes']['capturar']) {
                // --------------------------------------
                case 3: // Depurar
                        $depurar  = "<h4>Exceção lançada no arquivo: " .
                        $excecao->getFile() .
                        " - Na linha: " . $excecao->getLine() .
                        "</h4>\n" .
                        "<h4>Rastro do stack trace:</h4><ol>";
                        $pilha = $excecao->getTrace();
                        array_unshift($pilha, array('file'=> $excecao->getFile(), 'line'=> $excecao->getLine()));
                        foreach ($pilha as $objPilha) {
                                if (!file_exists(@$objPilha['file'])) continue;
                                $depurar .= "<li><span style='cursor:pointer' onclick='this.nextSibling.style.display = this.nextSibling.style.display == \"none\" ? \"block\" : \"none\" '>
                                $objPilha[file]: linha $objPilha[line]</span>";
                                $depurar .= "<div style='background-color: #EEEEEE; " .
                                "padding: 10px; display: none; " . 
                                "border: 1px dashed #000000; margin: 3px " .
                                 "display: block'><pre>";
                                $fonte     = explode("<br />",
                                highlight_file($objPilha['file'], true));
                                $fonte_str = "";
                                foreach($fonte as $linha => $valor) {
                                        $linha++;
                                        if ($linha == $objPilha['line']) {
                                                $fonte_str .= sprintf("<b " .
                                  "style='background-color: " . 
                                  "#DDDDDD'>" . 
                                  "<span style='color: " . 
                                  "#000000'>%03d:</span> " .
                                  "%s</span></b>\n", $linha, 
                                                $valor);
                                        } else {
                                                $fonte_str .= sprintf("<span style='color: " .
                                  "#000000'>%03d:</span>" .
                                  "%s\n", $linha, $valor);
                                        }
                                }
                                $depurar .= "$fonte_str</pre></div></li>\n";
                        }
                        $depurar .= "</ol>";
                case 2: // Detalhar
                        $detalhar = "<h4>Pilha de chamadas</h4>".
                        "<div style='background-color: #EEEEEE; " . 
                        "padding: 10px; " . 
                        "white-space: pre; border: 1px dashed " . 
                        "#000000; margin: 3px'>" . 
                        $excecao->getTraceAsString() . "</div>\n";
                        $detalhar .= "<h4>Var Export da Exceção</h4>".
                        "<div style='background-color: #EEEEEE; " . 
                        "padding: 10px; " . 
                        "white-space: pre; border: 1px dashed " . 
                        "#000000; margin: 3px'>" . 
                        var_export($excecao, true) . "</div>\n";
                case 1: // Capturar
                        $capturar = "<h3>Exceção Capturada</h3>" .  
                        "<h4>" . $excecao->getCode() . " - " . 
                        $excecao->getMessage() . "</h4>\n";
                        break;
                        // --------------------------------------
                case 0: // Não capturar
                        // Relançamento da exceção
                        throw $excecao;
                        break;
            }

            $conteudo = $capturar . $detalhar . $depurar;

            switch ($excecao->getCode()) {
                case self::MODULO_INEXISTENTE:
                case self::ACAO_INEXISTENTE:
                case self::VISAO_INEXISTENTE:
                    Facil::despacharErro(404, $conteudo);
                    break;
                case self::ACAO_PROTEGIDA:
                    Facil::despacharErro(403, $conteudo);
                    break;
                case self::PARAMETROS_INSUFICIENTES:
                case self::EXCECAO_NAO_CAPTURADA:
                default:
                    Facil::despacharErro(500, $conteudo);
                    break;
            }
            exit();
    }
}
?>
