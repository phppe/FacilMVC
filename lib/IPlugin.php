<?php
namespace lib;
/**
 * Interface geral dos plugins do framework
 *
 */
interface IPlugin {
	/**
	 * Método utilizado para executar operações
	 * no momento do carregamento da biblioteca
	 *
	 */
	public function carregar();
}
?>