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

namespace RepoWatch\Ext\Twig;

use Zion\Menu\Menu;
use Zion\Exception\Exception;
use Pixel\Twig\Carregador as CarregadorPixel;
use Base\Sistema\Modulo\ModuloClass;
use App\Notificacao\Notificacao;
use Base\Sistema\Organograma\OrganogramaForm;
use Base\Sistema\Organograma\OrganogramaClass;
use App\Sistema\Ajuda\AjudaView;
use Zion\Arquivo\ManipulaDiretorio;

class Carregador extends CarregadorPixel
{

    protected $dir;

    public function __construct($namespace = '')
    {
        $this->dir = new ManipulaDiretorio();
        parent::__construct($namespace, [$this->interpretaNamespace($namespace) .'/Tema/Vendor/Pixel/1.3.0/views']);

        $urlStorage = new \Twig_SimpleFunction('urlStorage', function ($dadosImage, $size = false) {
            if(isset($dadosImage['data']) and isset($dadosImage['hash'])){
                //return 'http://beta.meusiteturbinado.com/' .'Storage/' . $dadosImage['data'] .'/'. ($size ? $size .'/' : NULL) . $dadosImage['hash'];
                return SIS_URL_BASE_STORAGE . $dadosImage['data'] .'/'. ($size ? $size .'/' : NULL) . $dadosImage['hash'];
            } else {
                return false;
            }
        });
        
        $this->twig()->addFunction($urlStorage);
        
        $this->ajuda();
        $this->menu();
        $this->pageHeader();
        $this->organogramaHeader();
        $this->notificacoes();
        $this->erro();
        $this->trataLegenda();
        $this->getOrganogramaCod();
        $this->getMeuUsuario();
        $this->getChatConfig();
    }

    /**
     * A partir de um namespace, monta um caminho usando o diretorio base do
     * projeto definido na constante SIS_DIR_BASE. Caso o namespace seja passado
     * em branco, uma string vazia e retornada.
     *
     * @param string $namespace
     * @return string
     */
    private function interpretaNamespace($namespace)
    {
        if ($namespace !== '') {

            $caminho = \SIS_DIR_BASE_APP;

            return $this->dir->padronizaDiretorio($caminho, '/');
        }

        return $namespace;
    }
    
    private function ajuda()
    {
        $ajuda = new \Twig_SimpleFunction('ajuda', function ($hash = null) {

            $ajudaViewClass = new AjudaView();
            /*
            if(\defined('MODULONOME')) {
                $moduloNome = \MODULONOME;
            } else {
                $moduloNome = false;
            }
             * 
             */
            
            $dados = $ajudaViewClass->getAjuda(\MODULO, $hash);

            return $this->twig()->render('ajuda.html.twig', [
                        'form' => [
                            'ajuda' => $dados
                        ]
            ]);
        });

        $this->twig()->addFunction($ajuda);
    }  
    
    private function menu()
    {
        $menu = new \Twig_SimpleFunction('menu', function () {

            $m = new Menu();

            $dados = [
                'titulo' => \SIS_NOME_PROJETO,
                'versao' => \SIS_RELEASE,                
                'nomeUsuario' => $_SESSION['pessoaFisicaNome'],
                'avatarUsuario' => $_SESSION['pessoaFisicaAvatar'],
                'menu' => $m->geraMenu(true)
            ];

            return $this->twig()->render('menu.html.twig', $dados);
        });

        $this->twig()->addFunction($menu);
    }

    protected function organogramaHeader()
    {
        $organogramaHeader = new \Twig_SimpleFunction('organogramaHeader', function ($pos) {

            $orgForm = new OrganogramaForm();
            $orgClass = new OrganogramaClass();

            $organograma = [
                'organograma' => $orgForm->getOrganogramaTopoForm(),
                'resetOrganograma' => $orgClass->getResetOrganograma()
            ];

            return $organograma[$pos];
        });

        $this->twig()->addFunction($organogramaHeader);
    }

    private function notificacoes()
    {
        $notificacoes = new \Twig_SimpleFunction('notificacoes', function () {

            if(isset($_SESSION['usuarioCod'])){
                return (new Notificacao())->getUltimasNotificacoes($_SESSION['usuarioCod']);
            } else {
                return ['handler' => ''];
            }
        });

        $this->twig()->addFunction($notificacoes);
    }

    private function erro()
    {
        $erro = new \Twig_SimpleFunction('erro', function ($exception) {

            return ['exception' => $exception,
                'exceptionTrace' => Exception::getMessageTrace($exception),
                'env' => \SIS_RELEASE,
                'debug' => \SIS_DEBUG,
                'requestData' => $_REQUEST,
                'sessionData' => $_SESSION];
        });

        $this->twig()->addFunction($erro);
    }

    private function pageHeader()
    {

        $pageHeader = new \Twig_SimpleFunction('pageHeader', function ($pos) {

            if (\defined('MODULO')) {

                $modulo = (new ModuloClass)->getDadosModulo(\MODULO);
                
                if (empty($modulo)) {

                    $dadosModulo = [
                        'titulo' => \SIS_NOME_PROJETO,
                        'modulonome' => $modulo['modulonome'],
                        'moduloclass' => $modulo['moduloclass'],
                        'modulodesc' => $modulo['modulodesc'],
                        'versao' => \SIS_RELEASE,
                        'nomeUsuario' => $_SESSION['pessoaFisicaNome'],
                        'avatarUsuario' => $_SESSION['pessoaFisicaAvatar']
                    ];
                    
                } else {
                    
                    $dadosModulo = [
                        'titulo' => \SIS_NOME_PROJETO,
                        'modulonome' => $modulo['modulonome'],
                        'moduloclass' => $modulo['moduloclass'],
                        'modulodesc' => $modulo['modulodesc'],
                        'versao' => \SIS_RELEASE,
                        'nomeUsuario' => $_SESSION['pessoaFisicaNome'],
                        'avatarUsuario' => $_SESSION['pessoaFisicaAvatar']
                    ];
                    
                }                    

                return $dadosModulo[$pos];
            }

            return 'não definido';
        });

        $this->twig()->addFunction($pageHeader);
    }
    
    private function trataLegenda()
    {
        $this->twig()->addFunction(new \Twig_SimpleFunction('trataLegenda', function ($legenda) {
            
            if(\strlen($legenda) > 10){
                return \preg_replace([
                    '/class="table-footer"/',
                    '/<div class="col-sm-1">/',
                    '|</div>|',
                    '/btn-block/'
                ], [
                    '',
                    '',
                    '',
                    ''
                ], $legenda) .'</div></div>';
            } else {
                return NULL;
            }
            
        }));
    }
    
    private function getOrganogramaCod()
    {
        $data = new \Twig_SimpleFunction('getOrganogramaCod', function () {
            return $_SESSION['organogramaCod'];
        });
        
        $this->twig()->addFunction($data);
    }
    
    private function getMeuUsuario()
    {
        $data = new \Twig_SimpleFunction('getMeuUsuario', function () {
            return array(
                'id'    => $_SESSION['usuarioCod'],
                'image' => 'https://cdn4.iconfinder.com/data/icons/superheroes/512/batman-128.png',
                'name'  => 'Danilo'
            );
        });
        
        $this->twig()->addFunction($data);
    }
    
    private function getChatConfig() 
    {
        if(!defined('SIS_CHAT')){
            \define('SIS_CHAT', false);
            \define('SIS_APP_ID', '0');
        }
        
        $data = new \Twig_SimpleFunction('getChatConfig', function () {
            return array(
                'base_url'  => SIS_URL_DEFAULT_BASE,
                'client'    => SIS_APP_ID,
                'init'      => SIS_CHAT
            );
        });
        
        $this->twig()->addFunction($data);
    }
    
}