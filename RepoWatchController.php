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

use App\Ext\Core\Controller;
use Zion\Validacao\Valida;

class RepoWatchController extends Controller
{

    private $class;
    
    /** @var \Zion\Validacao\Valida Validação*/
    private $trata;
    
    public function __construct()
    {
        $this->class    = new RepoWatchClass();
        $this->trata    = Valida::instancia();
    } 
    
    protected function iniciar()
    {
        $retorno = [];
        
        $payload = \json_decode(\file_get_contents('php://input'), true);

        $dadosRequest = \filter_input_array(\INPUT_SERVER);
        
        try {

            $this->processaEvento($dadosRequest['HTTP_X_GITHUB_EVENT'], $payload);
            
        } catch (\Exception $e){
            return \json_encode([
                'sucesso' => false, 
                'retorno' => [
                    'mensagem' => $e->getMessage(),
                    'erro' => '<pre>'. $e->getTraceAsString() .'</pre>'
                ]
            ]);
        }

        return parent::jsonSucesso('OK');
    }
    
    private function processaEvento($evento, $payload)
    {
        switch ($evento) {

            case 'create':
                
                break;
            case 'delete':
                break;
            case 'push':
                $repositorio    = $this->class->getDadosRepo($payload['repository']);
                $branches       = $this->class->getBranches($repositorio['repositorioCod'], \substr($payload['repository']['branches_url'], 0, -9));
                $usuarios       = [1];
                
                $head           = $payload['head_commit'];
                
                $data           = $this->trata->data()->converteData(\substr($head['timestamp'], 0, 10));
                $hora           = \substr($head['timestamp'], 11, 5);
                
                $titulo     = 'Novo Push no repositório '. $repositorio['repositorioNome'];
                $descricao  = 'Último commit no branch '. $payload['ref']  .',<br /> por <strong>'. $head['author']['name'] .'</strong>, em <strong>'. $data .'</strong>, às <strong>'. $hora .'</strong>.<br />
                               Arquivos adicionados: <strong>'. \count($head['added']) .'</strong>. Removidos: <strong>'. \count($head['removed']) .'</strong>. Alterados: <strong>'. \count($head['modified']) .'</strong>';
                $warnLevel  =  'warning';
                $icon       =  'fa-github';
                $link       = $head['url'];
                
                foreach($usuarios as $usuarioCod){
                    $this->class->enviaNotificacao($usuarioCod, $titulo, $descricao, $warnLevel, $icon, $link);
                }

                break;
            case 'commit_comment':
                break;
            case 'issues':
                break;
            case 'pull_request':

                $dadosPullRequest = $this->class->getDadosPullRequest($payload);

                if($payload['action'] === 'opened' and isset($dadosPullRequest['id'])) {
                    $usuarios       = [1];

                    $pull           = $payload['pull_request'];

                    $head           = $pull['head'];

                    $data           = $this->trata->data()->converteData(\substr($pull['created_at'], 0, 10));
                    $hora           = \substr($pull['created_at'], 11, 5);

                    $user           = $this->class->getDadosAPI($payload['sender']['url']);

                    $stats          = $this->class->getStatsPull($pull['commits_url']);

                    $titulo     = 'Novo Pull Request no repositório '. $payload['repository']['name'];
                    $descricao  = 'Aberto por <strong>'. $user['name'] .'</strong>, em <strong>'. $data .'</strong>, às <strong>'. $hora .'</strong>.<br />
                                   Arquivos Alterados: <strong>'. $stats['files'] .'</strong>. Adições: <strong>'. $stats['add'] .'</strong>. Remoções: <strong>'. $stats['del'] .'</strong>';
                    $warnLevel  =  'danger';
                    $icon       =  'fa-github';
                    $link       = $pull['html_url'];

                    foreach($usuarios as $usuarioCod){
                        $this->class->enviaNotificacao($usuarioCod, $titulo, $descricao, $warnLevel, $icon, $link);
                    }
                }

                break;
            case 'issue_comment':
                break;
            case 'pull_request_review_comment':
                break;
            default:
                break;
        }
    }
}
