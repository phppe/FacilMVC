<?php
namespace config;
/**
 * Classe que terá o método capaz de localizar nossas classes
 */
class Autoload {
	/**
	 * Método que será chamado pelo PHP sob demanda para carregar as classes dos sistemas
	 * 
	 * @param string $nome Nome da classe
	 * @return unknown_type
	 */
	public static function load($nome) {
		$arquivo = RAIZ . DS . str_replace("\\", DS, $nome) . ".php";
		if (file_exists($arquivo)) {
			include $arquivo;
		}
	}
}

spl_autoload_register(array("config\\Autoload", 'load'));
?>
