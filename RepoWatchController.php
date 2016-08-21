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
use RepoWatch\Ext\Twig\Carregador;
use Zion\Validacao\Valida;
use RepoWatch\Telegram\Telegram;
use RepoWatch\WhatsApp\Whatsapp;

class RepoWatchController extends Controller
{

    private $class;
    private $carregador;

    /** @var \Zion\Validacao\Valida Validacao. */
    private $trata;
    
    /** @var Telegram Objeto da API de integracao com o Telegram. */
    private $telegram;
    
    /** @var Whatsapp Objeto da API de integracao com o Whats. */
    private $whatsapp;

    /** @var bool usar configurações de dev ou production */
    private $debug = true;
    
    /** @var string Id da conversa com o contato ou grupo que ira receber as notificacoes. Telegram */
    private $chatId;//Id Rinzler: 159867452

    /** @var string Id da conversa com o contato ou grupo que ira receber as notificacoes. Whatsapp */
    private $jID;//Id Rinzler: 159867452

//SuKDfv7YTH8lQk1uF3F9JbSpfEY=
//16464574271

    public function __construct()
    {
        $this->class        = new RepoWatchClass();
        $this->carregador   = new Carregador(__NAMESPACE__);
        $this->trata        = Valida::instancia();

        $this->telegram = new Telegram('bot219721426:AAGO9F8YIh0grhp41Ww_tCMoBnG36TUeQys');
        $this->whatsapp = new Whatsapp('16464574271', 'SuKDfv7YTH8lQk1uF3F9JbSpfEY=');

        $this->chatId   = ($this->debug === false ? '-157961528' : '159867452');//true = set Rinzler chatId, false = set BRA Dev Team Group chatId
        $this->jID      = ($this->debug === false ? '556593152857-1471434261@g.us' : '556593152857');//true = set Rinzler jID, false = set BRA Dev Team Group jID
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

                $dadosIssue = $this->class->getDadosIssue($payload);
                $issue          = $payload['issue'];

                if($payload['action'] === 'created' and $issue['state'] == 'open' and isset($dadosIssue['id'])) {
                    $usuarios       = [1];

                    $issue          = $payload['issue'];
                    $comment        = $payload['comment'];
                    
                    $mencionados    = $this->getUsuariosMencionados($payload['comment']);
                    
                    $tipo           = (\preg_match('/[pull]{4}/', $issue['html_url']) === true ? "no pull request" : "na issue");

                    $data           = $this->trata->data()->converteData(\substr($issue['created_at'], 0, 10));
                    $hora           = \substr($issue['created_at'], 11, 5);

                    $user           = $this->class->getDadosAPI($comment['user']['url']);

                    $titulo     = 'Nova interação '. $tipo .' de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio '
                                . '<a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>:\n\n";
                    
                    if($mencionados['count'] > 0){
                        $descricao = 'O usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a> adicionou um novo comentário mencionando o(s) usuário(s):\n";
                        $descricao .= $mencionados['texto'];
                    } else {
                        $descricao = 'O usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a> adicionou um novo comentário.\n";
                    }
                    
                    $descricao .= "----\n";
                    
                    $this->telegram->sendMessage($titulo . $descricao, $this->chatId);
                    //$this->whatsapp->sendMessage($titulo . $descricao, $this->jID);
                }
                
                break;
            case 'issues':

                $dadosIssue = $this->class->getDadosIssue($payload);
                
                $usuarios       = [1];//UsuarioCod do SiprevCloud

                $user           = $this->class->getDadosAPI($payload['sender']['url']);
                $repositorio    = $payload['repository'];
                $issue          = $payload['issue'];

                $data           = $this->trata->data()->converteData(\substr($issue['created_at'], 0, 10));
                $hora           = \substr($issue['created_at'], 11, 5);

                $repositorioIssueCod = $dadosIssue['repositorioIssueCod'];

                if($payload['action'] === 'opened' and isset($dadosIssue['id'])) {
                    
                    $assignees = $this->getUsuariosDesignados($payload, $repositorioIssueCod);

                    $titulo     = 'Nova Issue aberta no repositório <a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>\n\n";
                    $descricao  = 'A issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a> acaba de ser aberta por <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] .'</a>,'
                                  .' em <strong>'. $data .'</strong>, as <strong>'. $hora .'</strong>.';

                    if(\count($assignees['count']) > 0){
                        $this->class->registraAssigned($issue['assignees'], $repositorioIssueCod);
                        $descricao .= "\n\n". $assignees['texto'];
                    }

                } elseif($payload['action'] === 'closed' and isset($dadosIssue['id'])){

                    $titulo     = "Parabéns! Mais uma demanda implementada, testada e homologada.\n";
                    $descricao  = 'A issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositório <a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>"
                                .' acaba de ser fechada pelo usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a>.\n";

                } elseif($payload['action'] === 'reopened' and isset($dadosIssue['id'])){

                    $titulo = ' ';
                    $descricao = $this->carregador->render('telegram/issues_reopened.html.twig', [
                        'issue'         => $issue,
                        'repositorio'   => $repositorio,
                        'user'          => $user,
                    ]);

                } elseif($payload['action'] === 'assigned' and $issue['state'] == 'open' and isset($dadosIssue['id'])){

                    $assigneds = $this->getUsuariosDesignados($payload, $repositorioIssueCod);
                    $this->class->registraAssigned($issue['assignees'], $repositorioIssueCod);
                    if($assigneds['count'] > 0){
                        $titulo     = 'Nova interação na issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio '
                                    . '<a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>:";
                        $descricao = "\n\n". $assigneds['texto'];
                    } else {
                        return;
                    }
                } elseif($payload['action'] === 'unassigned' and $issue['state'] == 'open' and isset($dadosIssue['id'])){
                    //Apenas remove vínculo de usuarios com a issue.
                    return $this->class->registraAssigned($issue['assignees'], $repositorioIssueCod);

                } elseif($payload['action'] === 'labeled' and $issue['state'] = 'open' and isset($dadosIssue['id'])){
                    
                    $labels = $this->getLabels($issue['labels'], $payload['repository']['html_url']);
                    if(\in_array('trabalhando nisso', $labels) === true){
                        $titulo     = 'Nova interação na issue de número <a href="'. $issue['html_url'] .'" target="_blank">#'. $issue['number'] .'</a>, do repositorio '
                                    . '<a href="'. $payload['repository']['html_url'] .'" target="_blank">'. $payload['repository']['name'] ."</a>:\n";
                        $descricao  = 'O usuário <a href="'. $user['html_url'] .'" target="_blank">@'. $user['login'] ."</a> está trabalhando nesta tarefa.\n";
                    } else {
                        return;
                    }
                } else {
                    return;
                }

                $descricao .= "----\n";

                $warnLevel  =  'info';
                $icon       =  'fa-github';
                $link       = $issue['html_url'];
                $this->telegram->sendMessage($titulo . $descricao, $this->chatId);
                //$this->whatsapp->sendMessage($titulo . $descricao, $this->jID);
                
                if($payload['action'] === 'closed'){
                    $this->telegram->sendSticker('./Telegram/stickers/fist.webp', $this->chatId);
                } elseif($payload['action'] === 'reopened'){
                    $this->telegram->sendSticker('./Telegram/stickers/bugginho.png', $this->chatId);
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
    
    public function getUsuariosDesignados($payload, $repositorioIssueCod)
    {
        $issue          = $payload['issue'];
        $assignees      = $issue['assignees'];
        $user           = $issue['user'];
        $sender         = $payload['sender'];
        $selfAssigned   = NULL;
        $designados     = NULL;
        $count          = 0;

        foreach($assignees as $userAssigned){
            
            if($this->class->verificaAssigned($userAssigned, $repositorioIssueCod) === false){
                if($userAssigned['id'] == $sender['id']){
                    $selfAssigned = 'O usuário <a href="'. $userAssigned['html_url'] .'" target="_blank">@'. $userAssigned['login'] ."</a> se auto-nomeu para esta tarefa.\n";
                } else {
                    $designados .= 'Esta tarefa foi atribuída ao  usuário <a href="'. $userAssigned['html_url'] .'" target="_blank">@'. $userAssigned['login'] ."</a>.\n";
                }
                $count++;
            }
        }
        
        return [
            'texto' => $selfAssigned . $designados,
            'count' => $count
        ];
    }

    public function getLabels($labels, $repoUrl)
    {
        $definicao  = [];
        $labelsUrl  = $repoUrl .'/labels/';

        foreach($labels as $label){
            $definicao['text'] = '<a href="'. $labelsUrl . $label['name'] .'" target="_blank">'. \strtoupper($label['name']) ."</a>\n";
            $definicao['name'] = $label['name'];
        }

        return $definicao;
    }
    
    public function getUsuariosMencionados($comment)
    {
        $texto = '';
        $users = [];
        \preg_match_all('/\@[0-9A-z]{2,}/', $comment['body'], $users);
        $count = 0;

        if(\count($users[0]) > 0){
            foreach($users[0] as $user) {
                $dadosContributor = $this->class->getDadosContributor(\substr($user, 1));
                $texto .= '<a href="'. $dadosContributor['contributorurl'] .'" target="_blank">@'. $dadosContributor['contributorlogin'] ."</a>\n";
                $count++;
            }
        }

        $users['count'] = $count;
        return [
            'texto' => $texto,
            'count' => $count
        ];
    }
}
