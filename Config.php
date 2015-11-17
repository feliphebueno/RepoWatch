<?php
/**
*
*    Sappiens Framework
*    Copyright (C) 2014, BRA Consultoria
*
*    Website do autor: www.braconsultoria.com.br/sappiens
*    Email do autor: sappiens@braconsultoria.com.br
*
*    Website do projeto, equipe e documentação: www.sappiens.com.br
*   
*    Este programa é software livre; você pode redistribuí-lo e/ou
*    modificá-lo sob os termos da Licença Pública Geral GNU, conforme
*    publicada pela Free Software Foundation, versão 2.
*
*    Este programa é distribuído na expectativa de ser útil, mas SEM
*    QUALQUER GARANTIA; sem mesmo a garantia implícita de
*    COMERCIALIZAÇÃO ou de ADEQUAÇÃO A QUALQUER PROPÓSITO EM
*    PARTICULAR. Consulte a Licença Pública Geral GNU para obter mais
*    detalhes.
* 
*    Você deve ter recebido uma cópia da Licença Pública Geral GNU
*    junto com este programa; se não, escreva para a Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
*    02111-1307, USA.
*
*    Cópias da licença disponíveis em /Sappiens/_doc/licenca
*
*/

namespace RepoWatch;

require_once '../SiprevCloud/App/ConfigLite.php';

class Config
{

    public static $SIS_CFG;
    private static $SIS_INSTANCIA;

    private function __construct()
    {

        if (empty($_SESSION)) {
            \session_start();
        }

        require_once('../SiprevCloud/App/ConfigDatabase.php');                        
         //= (new \App\ConfigDatabase())->dataBases();

        \App\Config::$SIS_CFG['bases']['siprevcl_prod'] = [
            'host'      => '186.226.56.109',
            'banco'     => 'siprevcl_prod',
            'usuario'   => 'siprevcl_prod',
            'senha'     => 'v6n5g0a9',
            'driver'    => 'pdo_mysql'
        ];
        
        self::$SIS_CFG = \App\Config::$SIS_CFG;

        require_once \SIS_FM_BASE . 'Lib/vendor/autoload.php';   
    }

    public static function conf()
    {
        if (!isset(self::$SIS_INSTANCIA)) {
            self::$SIS_INSTANCIA = new \RepoWatch\Config();
        }

        return self::$SIS_INSTANCIA;
    }

}

\RepoWatch\Config::conf();

function sisErro($errno, $errstr, $errfile, $errline)
{
    throw new \Exception(\sprintf("%s: %s in %s on line %s", getErrorType($errno), $errstr, $errfile, $errline));
}

function getErrorType($errno)
{
    $erros = [1 => "Error",
        2 => "Warning",
        4 => "Parse error",
        8 => "Notice",
        16 => "Core error",
        32 => "Core warning",
        64 => "Compile error",
        128 => "Compile warning",
        256 => "User error",
        512 => "User warning",
        1024 => "User notice",
        6143 => "Undefined erro",
        2048 => "Strict error",
        4096 => "Recoverable error"
    ];

    return($erros[$errno] ? : $erros[6143]);
}

\set_error_handler("\\RepoWatch\\sisErro", \E_WARNING | \E_NOTICE);

require_once \SIS_FM_BASE . 'Lib/Zion/ClassLoader/Loader.php';

$_SESSION['autoLoaderReport'] = [];

(new \Zion\ClassLoader\Loader())
        ->setNameSpaces('Zion', \SIS_NAMESPACE_FRAMEWORK) //NameSpace do Framework
        ->setNameSpaces('Pixel', \SIS_NAMESPACE_FRAMEWORK) //NameSpace do Template
        ->setNameSpaces('Base', \SIS_NAMESPACE_BASE) //NameSpace dos módulos base
        ->setNameSpaces(\SIS_ID_NAMESPACE_PROJETO, \SIS_NAMESPACE_PROJETO) //NameSpace do Projeto
        ->setNameSpaces('Sites', \SIS_NAMESPACE_SITES)//NameSpace do Render
        ->setNameSpaces('RepoWatch', '/home/siprevcloudcom/public_html/')//NameSpace do Render
        ->inicio();
