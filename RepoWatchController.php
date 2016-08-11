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
use RepoWatch\Telegram\Telegram;

class RepoWatchController extends Controller
{

    private $class;
    
    /** @var \Zion\Validacao\Valida Validacao. */
    private $trata;
    
    /** @var Telegram Objeto da API de integracao com o Telegram. */
    private $telegram;
    
    /** @var string Id da conversa com o contato ou grupo que ira receber as notificacoes. */
    private $chatId = '159867452';


    public function __construct()
    {
        $this->class    = new RepoWatchClass();
        $this->trata    = Valida::instancia();
        $this->telegram = new Telegram('bot219721426:AAGO9F8YIh0grhp41Ww_tCMoBnG36TUeQys');
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
                
                $titulo     = 'Novo Push no repositÃ³rio '. $repositorio['repositorioNome'];
                $descricao  = 'Ãšltimo commit no branch '. $payload['ref']  .',<br /> por <strong>'. $head['author']['name'] .'</strong>, em <strong>'. $data .'</strong>, Ã s <strong>'. $hora .'</strong>.<br />
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

                    $titulo     = 'Novo Pull Request no repositÃ³rio '. $payload['repository']['name'];
                    $descricao  = 'Aberto por <strong>'. $user['name'] .'</strong>, em <strong>'. $data .'</strong>, Ã s <strong>'. $hora .'</strong>.<br />
                                   Arquivos Alterados: <strong>'. $stats['files'] .'</strong>. AdiÃ§Ãµes: <strong>'. $stats['add'] .'</strong>. RemoÃ§Ãµes: <strong>'. $stats['del'] .'</strong>';
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
            case 'issues':

                $dadosIssue = $this->class->getDadosIssue($payload);
                
                $usuarios       = [1];

                $issue           = $payload['issue'];

                $data           = $this->trata->data()->converteData(\substr($issue['created_at'], 0, 10));
                $hora           = \substr($issue['created_at'], 11, 5);

                $user           = $this->class->getDadosAPI($issue['user']['url']);

                if($payload['action'] === 'opened' and isset($dadosIssue['id'])) {

                    $titulo     = 'Nova Issue aberta no repositorio <a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>\n\n";
                    $descricao  = 'A issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a> acaba de ser aberta por <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] .'</a>,'
                                  .' em <strong>'. $data .'</strong>, as <strong>'. $hora .'</strong>.';

                    if(\count($issue['assignees']) > 0){
                        $descricao .= "\n\n". $this->getUsuariosDesignados($issue);
                    }

                } elseif($payload['action'] === 'closed' and isset($dadosIssue['id'])){

                    $titulo     = "Parabéns! Mais uma demanda implementada, testada e homologada.\n";
                    $descricao  = 'A issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio <a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>"
                                .' acaba de ser encerrada pelo usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a>.";

                } elseif($payload['action'] === 'assigned' and isset($dadosIssue['id'])){

                    $titulo     = 'Nova interação na issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio '
                                . '<a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>";
                    if(\count($issue['assignees']) > 0){
                        $descricao = "\n". $this->getUsuariosDesignados($issue);
                    }
                } elseif($payload['action'] === 'labeled' and isset($dadosIssue['id'])){
                    
                    $titulo     = 'Nova interação na issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio '
                                . '<a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>\n";
                    $descricao  = 'O usuário usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a> alterou os labels desta issue para:\n";
                    $descricao  .= $this->getLabels($issue['labels'], $payload['repository']['html_url']);
                    
                }

                $descricao .= "\n\n\n";

                $warnLevel  =  'info';
                $icon       =  'fa-github';
                $link       = $issue['html_url'];
                $this->telegram->sendMessage($titulo . $descricao, $this->chatId);
                
                if($payload['action'] === 'closed'){
                    $this->telegram->sendSticker('BQADAQADQAADyIsGAAGMQCvHaYLU_AI', $this->chatId);
                }

                foreach($usuarios as $usuarioCod){
                    $this->class->enviaNotificacao($usuarioCod, $titulo, $descricao, $warnLevel, $icon, $link);
                }

                break;
            case 'pull_request_review_comment':
                break;
            default:
                break;
        }
    }
    
    public function getUsuariosDesignados($issue)
    {
        $assignees      = $issue['assignees'];
        $user           = $issue['user'];
        $selfAssigned   = NULL;
        $designados     = NULL;

        foreach($assignees as $userAssigned){
            if($userAssigned['id'] == $user['id']){
                $selfAssigned = 'O usuário <a href="'. $userAssigned['html_url'] .'" target="_blank">@'. $userAssigned['login'] ."</a> se auto-nomeu para esta tarefa.\n";
            } else {
                $designados .= 'Esta tarefa foi atribuída ao  usuário <a href="'. $userAssigned['html_url'] .'" target="_blank">@'. $userAssigned['login'] ."</a>.\n";
            }
        }
        
        return $selfAssigned . $designados;
    }

    public function getLabels($labels, $repoUrl)
    {
        $definicao  = NULL;
        $labelsUrl  = $repoUrl .'/labels/';

        foreach($labels as $label){
            $definicao .= '<a href="'. $labelsUrl . $label['name'] .'" target="_blank">'. \strtoupper($label['name']) ."</a>\n";
        }
        
        return $definicao;
    }
}
