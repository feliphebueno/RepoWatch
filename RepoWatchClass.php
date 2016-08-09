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

use Zion\Banco\Conexao;
use App\Ext\Crud\CrudUtil;
use GuzzleHttp\Client;
use Zion\Arquivo\ManipulaImagem;

class RepoWatchClass extends RepoWatchSql
{
    
    private $chavePrimaria;
    private $tabela;
    private $precedencia;
    private $crudUtil;
    private $banco;
    protected $con;
    
    public function __construct($cod = '')
    {
        $this->cod = $cod;
        $this->con = Conexao::conectar();
        $this->crudUtil = new CrudUtil('siprevcl_prod');

        parent::__construct();
    }
    
    public function log($data)
    {
        $filename = 'log/'. \date('d-m-Y_H-i-s') .'.log';
        return \file_put_contents($filename, $data);
    }
       
    public function getBranches($repositorioCod, $branches_url) 
    {
        $dadosBranches = $this->getDadosAPI($branches_url);

        $branches = [];
        
        foreach($dadosBranches as $branch){
            $branches[$branch['name']] = $this->setBranches($repositorioCod, $branch);
        }
        
        return $branches;
    }
    
    private function setBranches($repositorioCod, $dados)
    {
        $name       = $dados['name'];

        $retorno = [
            'branchNome'        => $name,
        ];
        
        $branch = $this->con->execLinha(parent::getBranchSql($name));
        
        //User already exists.
        if(\count($branch) > 0){
            $retorno['repositorioBranchCod']    = $branch['repositoriobranchcod'];
            $repositorioBranchCod               = $branch['repositoriobranchcod'];
        } else {         

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('repositorioCod', $repositorioCod);
            $objForm->set('repositorioBranchNome', $name);
            $objForm->set('repositorioBranchStatus', 'A');

            $campos = [
                'repositorioCod',
                'repositorioBranchNome',
                'repositorioBranchStatus'
            ];

            $repositorioBranchCod = $this->crudUtil->insert('repositorio_branch', $campos, $objForm, ['organogramaCod']);
            $retorno['repositorioBranchCod']  = $repositorioBranchCod;
        }
        
        $retorno['commits'] = [
            $dados['commit']['sha'] = $this->getBranchCommits($repositorioBranchCod, $dados['commit'])
        ];

        return $retorno;
    }
    
    private function getBranchCommits($repositorioBranchCod, $dados)
    {
        $sha    = $dados['sha'];

        $retorno = [
            'sha'        => $sha,
        ];
        
        $commit = $this->con->execLinha(parent::getCommitSql($sha));
        
        //User already exists.
        if(\count($commit) > 0){
            $retorno['commitCod']    = $commit['repositoriobranchcommitcod'];
        } else {

            $dadosCommit        = $this->getDadosAPI($dados['url']);          
            $contributor        = $this->getDadosUser($dadosCommit['author']);
            
            $objForm = new \App\Ext\Form\Form();
            $objForm->set('repositorioBranchCod', $repositorioBranchCod);
            $objForm->set('contributorCod', $contributor['contributorCod']);
            $objForm->set('repositorioBranchCommitSha', $sha);
            $objForm->set('repositorioBranchCommitMensagem', $dadosCommit['commit']['message']);
            $objForm->set('repositorioBranchCommitUrl', $dadosCommit['html_url']);
            $objForm->set('repositorioBranchCommitComentarios', $dadosCommit['commit']['comment_count']);
            $objForm->set('repositorioBranchCommitAdicoes', $dadosCommit['stats']['additions']);
            $objForm->set('repositorioBranchCommitRemocoes', $dadosCommit['stats']['deletions']);
            $objForm->set('repositorioBranchCommitArquivos', \count($dadosCommit['files']));
            $objForm->set('repositorioBranchCommitData', $dadosCommit['commit']['author']['date']);

            $campos = [
                'repositorioBranchCod',
                'contributorCod',
                'repositorioBranchCommitSha',
                'repositorioBranchCommitMensagem',
                'repositorioBranchCommitUrl',
                'repositorioBranchCommitComentarios',
                'repositorioBranchCommitAdicoes',
                'repositorioBranchCommitRemocoes',
                'repositorioBranchCommitArquivos',
                'repositorioBranchCommitData'
            ];

            $repositorioBranchCommitCod             = $this->crudUtil->insert('repositorio_branch_commit', $campos, $objForm, ['organogramaCod']);
            $retorno['repositorioBranchCommitCod']  = $repositorioBranchCommitCod;
        }

        return $retorno;
    }
    
    public function getDadosRepo($dados)
    {
        $id         = $dados['id'];
        $name       = $dados['name'];
        $full_name  = $dados['full_name'];

        $retorno = [
            'repositorioNome'       => $name,
            'id'                    => $id,
            'repositorioFullName'   => $full_name
        ];
        
        $repositorio = $this->con->execLinha(parent::getRepositorioSql($id));
        
        //Repo already exists.
        if(\count($repositorio) > 0){

            $retorno['repositorioCod']  = $repositorio['repositoriocod'];

        } else {

            $urlRepoAPI         = 'https://api.github.com/repos/'. $dados['full_name'];
            $dadosRepositorio   = $this->getDadosAPI($urlRepoAPI);
            $dadosOwner         = $this->getDadosUser($dados['owner']);

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('repositorioOwnerCod', $dadosOwner['contributorCod']);
            $objForm->set('repositorioId', $dadosRepositorio['id']);
            $objForm->set('repositorioNome', $dadosRepositorio['name']);
            $objForm->set('repositorioFullName', $dadosRepositorio['full_name']);
            $objForm->set('repositorioDescricao', $dadosRepositorio['description']);
            $objForm->set('repositorioPrivado', ($dadosRepositorio['private'] == 'true' ? 'S' : 'N'));
            $objForm->set('repositorioUrl', $dadosRepositorio['html_url']);
            $objForm->set('repositorioUrlTeam', $dadosRepositorio['teams_url']);
            $objForm->set('repositorioUrlBranches', $dadosRepositorio['branches_url']);
            $objForm->set('repositorioDataCriacao', $dadosRepositorio['created_at']);
            $objForm->set('repositorioDataUltimaAtualizacao', $dadosRepositorio['updated_at']);

            $campos = [
                'repositorioOwnerCod',
                'repositorioId',
                'repositorioNome',
                'repositorioFullName',
                'repositorioDescricao',
                'repositorioPrivado',
                'repositorioUrl',
                'repositorioUrlTeam',
                'repositorioUrlBranches',
                'repositorioDataCriacao',
                'repositorioDataUltimaAtualizacao',
            ];

            $repositorioCod = $this->crudUtil->insert('repositorio', $campos, $objForm, ['organogramaCod']);
            $retorno['repositorioCod']  = $repositorioCod;
        }

        return $retorno;
    }
    
    public function getDadosPullRequest($dados)
    {
        $pullRequest = $dados['pull_request'];
        
        $id         = $pullRequest['id'];
        $name       = $pullRequest['title'];

        $retorno = [
            'titulo'       => $name,
            'id'           => $id
        ];
        
        $dadosPullRequest = $this->con->execLinha(parent::getPullRequestSql($id));
        
        //User already exists.
        if(\count($dadosPullRequest) > 0){

            $retorno['repositorioPullCod']  = $dadosPullRequest['repositoriopullcod'];
            $merged = ($pullRequest['merged'] == 'true' ? 'M' : 'N');
            
            if($dadosPullRequest['repositoriopullstatus'] !== $merged){
                $objForm = new \App\Ext\Form\Form();
                $objForm->set('repositorioPullStatus', "M");
                $this->crudUtil->update('repositorio_pull', ['repositorioPullStatus'], $objForm, ['repositorioPullCod' => $dadosPullRequest['repositoriopullcod']], [], ['organogramaCod']);
            }

        } else {

            $dadosUser         = $this->getDadosUser($pullRequest['user']);
            $dadosRepo         = $this->getDadosRepo($dados['repository']);

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('repositorioCod', $dadosRepo['repositorioCod']);
            $objForm->set('contributorCod', $dadosUser['contributorCod']);
            $objForm->set('repositorioPullId', $pullRequest['id']);
            $objForm->set('repositorioPullTitulo', $pullRequest['title']);
            $objForm->set('repositorioPullMensagem', $pullRequest['body']);
            $objForm->set('repositorioPullUrl', $pullRequest['url']);
            $objForm->set('repositorioPullMesclavel', ($pullRequest['mergeable'] == 'true' ? 'S' : 'N'));
            $objForm->set('repositorioPullComentarios', $pullRequest['comments']);
            $objForm->set('repositorioPullCommits', $pullRequest['commits']);
            $objForm->set('repositorioPullAdicoes', $pullRequest['additions']);
            $objForm->set('repositorioPullRemocoes', $pullRequest['deletions']);
            $objForm->set('repositorioPullArquivosAlterados', $pullRequest['changed_files']);
            $objForm->set('repositorioPullData', $pullRequest['created_at']);
            $objForm->set('repositorioPullDataMerged', $pullRequest['merged_at']);
            $objForm->set('repositorioPullStatus', ($pullRequest['merged'] == 'true' ? 'M' : 'N'));

            $campos = [
                'repositorioCod',
                'contributorCod',
                'repositorioPullId',
                'repositorioPullTitulo',
                'repositorioPullMensagem',
                'repositorioPullUrl',
                'repositorioPullMesclavel',
                'repositorioPullComentarios',
                'repositorioPullCommits',
                'repositorioPullAdicoes',
                'repositorioPullRemocoes',
                'repositorioPullArquivosAlterados',
                'repositorioPullData',
                'repositorioPullDataMerged',
                'repositorioPullStatus',
            ];

            $repositorioPullCod = $this->crudUtil->insert('repositorio_pull', $campos, $objForm, ['organogramaCod']);
            $retorno['repositorioPullCod']  = $repositorioPullCod;
        }

        return $retorno;
    }
    
    public function getDadosIssue($dados)
    {
        $issue = $dados['issue'];
        
        $id         = $issue['id'];
        $name       = $issue['title'];

        $retorno = [
            'titulo'       => $name,
            'id'           => $id
        ];
        
        $dadosIssue = $this->con->execLinha(parent::getIssueSql($id));
        
        //User already exists.
        if(\count($dadosIssue) > 0){

            $retorno['repositorioPullCod']  = $dadosIssue['repositorioissuecod'];
            $closed = ($issue['state'] == 'open' ? 'O' : 'C');
            
            if($dadosIssue['state'] !== $closed){
                $objForm = new \App\Ext\Form\Form();
                $objForm->set('repositorioIssueStatus', "C");
                $this->crudUtil->update('repositorio_issue', ['repositorioIssueStatus'], $objForm, ['repositorioIssueCod' => $dadosIssue['repositorioissuecod']], [], ['organogramaCod']);
            }

        } else {

            $dadosUser         = $this->getDadosUser($issue['user']);
            $dadosRepo         = $this->getDadosRepo($dados['repository']);

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('repositorioCod', $dadosRepo['repositorioCod']);
            $objForm->set('contributorCod', $dadosUser['contributorCod']);
            $objForm->set('repositorioIssueId', $issue['id']);
            $objForm->set('repositorioIssueTitulo', $issue['title']);
            $objForm->set('repositorioIssueMensagem', $issue['body']);
            $objForm->set('repositorioIssueUrl', $issue['url']);
            $objForm->set('repositorioIssueComentarios', $issue['comments']);
            $objForm->set('repositorioIssueData', $issue['created_at']);
            $objForm->set('repositorioIssueDataClosed', $issue['merged_at']);
            $objForm->set('repositorioIssueStatus', ($issue['state'] == 'open' ? 'O' : 'C'));

            $campos = [
                'repositorioCod',
                'contributorCod',
                'repositorioIssueId',
                'repositorioIssueTitulo',
                'repositorioIssueMensagem',
                'repositorioIssueUrl',
                'repositorioIssueComentarios',
                'repositorioIssueData',
                'repositorioIssueDataClosed',
                'repositorioIssueStatus',
            ];

            $idRegistro = $this->crudUtil->insert('repositorio_issue', $campos, $objForm, ['organogramaCod']);
            $retorno['repositorioIssueCod']  = $idRegistro;
        }

        return $retorno;
    }
    
    public function getDadosUser($dados)
    {
        if(isset($dados['login'])){
            $login  = $dados['login'];
            $id     = $dados['id'];

            $retorno = [
                'login' => $login,
                'id'    => $id
            ];
            $contributor = $this->con->execLinha(parent::getContributorSql($login));
        } else {
            $userName   = $dados['name'];
            $email      = $dados['email'];

            $retorno = [
                'userName'  => $userName,
                'email' => $email
            ];
            
            $contributor = $this->con->execLinha(parent::getContributorSql($userName));
        }
        
        //User already exists.
        if(\count($contributor) > 0){

            $retorno['contributorCod']  = $contributor['contributorcod'];
            $retorno['contributorNome'] = $contributor['contributornome'];

        } else {

            $urlUserAPI         = (isset($dados['url']) ? $dados['url'] : 'https://api.github.com/users/'. $userName);
            $dadosContributor   = $this->getDadosAPI($urlUserAPI);

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('contributorNome', $dadosContributor['name']);
            $objForm->set('contributorLogin', $dadosContributor['login']);
            $objForm->set('contributorId', $dadosContributor['id']);
            $objForm->set('contributorEmail', (isset($email) ? $email : $dadosContributor['email']));
            $objForm->set('contributorAvatar', $dadosContributor['avatar_url']);
            $objForm->set('contributorUrl', $dadosContributor['html_url']);
            $objForm->set('contributorLocation', $dadosContributor['location']);
            $objForm->set('contributorUltimaAtualizacao', $dadosContributor['updated_at']);

            $campos = [
                'contributorNome',
                'contributorLogin',
                'contributorId',
                'contributorEmail',
                'contributorAvatar',
                'contributorUrl',
                'contributorLocation',
                'contributorUltimaAtualizacao'
            ];

            $contributorCod = $this->crudUtil->insert('contributor', $campos, $objForm, ['organogramaCod']);
            $retorno['contributorCod']  = $contributorCod;
            $retorno['contributorNome'] = $dadosContributor['name'];
        }

        return $retorno;
    }
    
    public function getDadosAPI($url)
    {
        $client     = new \GuzzleHttp\Client(['verify' => false]);
        $response   = $client->get($url, [
            'auth' => [
                '<github_user>',
                '<user_token>'
            ]
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
    
    public function enviaNotificacao($usuarioCod, $titulo, $descricao, $warnLevel, $icon, $link)
    {
        $objForm = new \App\Ext\Form\Form();
        $objForm->set('usuarioCod', 2);
        $objForm->set('notificacaoUsuarioCod', $usuarioCod);
        $objForm->set('notificacaoTitulo', $titulo);
        $objForm->set('notificacaoDesc', $descricao);
        $objForm->set('notificacaoWarnLevel', $warnLevel);
        $objForm->set('notificacaoIcon', $icon);
        $objForm->set('notificacaoDataHora', \date('Y-m-d H:i:s'));
        $objForm->set('notificacaoLink', $link);

        $campos = [
            'usuarioCod',
            'notificacaoUsuarioCod',
            'notificacaoTitulo',
            'notificacaoDesc',
            'notificacaoWarnLevel',
            'notificacaoIcon',
            'notificacaoDataHora',
            'notificacaoLink'
        ];
        
        //t1
        $crudUtil = new CrudUtil('siprevcl_bd');
        $crudUtil->insert('_notificacao', $campos, $objForm, ['organogramaCod']);
        
        //s2
        $crudUtilS2 = new CrudUtil('siprevcl_prod_s2');
        $crudUtilS2->insert('_notificacao', $campos, $objForm, ['organogramaCod']);

        return true;
    }
    
    public function getStatsPull($commitsUrl)
    {
        $commits = $this->getDadosAPI($commitsUrl);
        
        $files  = 0;
        $add    = 0;
        $del    = 0;
        
        foreach ($commits as $commit) {
            $dados = $this->getDadosAPI($commit['url']);
            
            $files  += \count($dados['files']);
            $add    += $dados['stats']['additions'];
            $del    += $dados['stats']['deletions'];
        }
        
        return [
            'files' => $files,
            'add'   => $add,
            'del'   => $del
        ];
    }
}