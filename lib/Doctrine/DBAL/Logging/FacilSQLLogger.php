<?php

namespace Doctrine\DBAL\Logging;

/**
 * SQL logger que gera saída em TMP
 *
 * 
 * @link    www.github.com/phppe/FacilMVC
 * @since   1.1
 * @author  Jose Berardo <berardo@especializa.com.br>
 */
class FacilSQLLogger implements SQLLogger {
    private $momento;
    private $caminho;
    
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null) {
        $this->momento = microtime(true);
        $this->caminho = TMP . DS . 'sqllogs';
        file_put_contents($this->caminho, $sql . PHP_EOL, FILE_APPEND);

        if ($params) {
            file_put_contents($this->caminho, var_export($params), FILE_APPEND);
    	}

        if ($types) {
            file_put_contents($this->caminho, var_export($types), FILE_APPEND);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery() {
        file_put_contents($this->caminho, sprintf('%.2f ms', (microtime(true) - $this->momento) * 1000));
    }
}
