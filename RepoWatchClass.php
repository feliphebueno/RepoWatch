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
        $this->crudUtil = new CrudUtil('siprevcl_bd');

        parent::__construct();
    }
    
    public function log($data)
    {
        $filename = 'log/'. \date('d-m-Y_H-i-s') .'.log';
        return \file_put_contents($filename, $data);
    }
    
    public function processaWebHook($payload)
    {
        $dadosUser  = $this->getDadosUser($payload['comment']['user']);
        $dadosRepo  = $this->getDadosRepo($payload['repository']);
        $branches   = $this->getBranches($dadosRepo['repositorioCod'], $payload['repository']['branches_url']);
        
        print "Resultado da requisição: \n Dados User: \n";
        print_r($dadosUser);
        print "Dados Repo: \n";
        print_r($dadosRepo);
        exit;
    }
    
    private function getBranches($repositorioCod, $branches_url) 
    {
        $url = \preg_replace('/[\\{\/branch\\}]{9}/', '', $branches_url);
        $branches = $this->getDadosAPI($url);

        foreach($branches as $branch){
            
        }
    }
    
    private function setBranches($dados)
    {
        $name       = $dados['name'];

        $retorno = [
            'branchNome'       => $name
        ];
        
        $branch = $this->con->execLinha(parent::getBranchSql($name));
        
        //User already exists.
        if(\count($branch) > 0){

            $retorno['repositorioCod']  = $repositorio['repositoriocod'];

        } else {

            $urlUserAPI         = (isset($dados['url']) ? $dados['url'] : NULL);
            $dadosRepositorio   = $this->getDadosAPI($urlUserAPI);
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

    }
    
    private function getDadosRepo($dados)
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
        
        //User already exists.
        if(\count($repositorio) > 0){

            $retorno['repositorioCod']  = $repositorio['repositoriocod'];

        } else {

            $urlUserAPI         = (isset($dados['url']) ? $dados['url'] : NULL);
            $dadosRepositorio   = $this->getDadosAPI($urlUserAPI);
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
    
    private function getDadosUser($dados)
    {
        $login  = $dados['login'];
        $id     = $dados['id'];

        $retorno = [
            'login' => $login,
            'id'    => $id
        ];
        
        $contributor = $this->con->execLinha(parent::getContributorSql($id));
        
        //User already exists.
        if(\count($contributor) > 0){

            $retorno['contributorCod']  = $contributor['contributorcod'];
            $retorno['contributorNome'] = $contributor['contributornome'];

        } else {

            $urlUserAPI     = (isset($dados['url']) ? $dados['url'] : NULL);
            $dadosContributor   = $this->getDadosAPI($urlUserAPI);

            $objForm = new \App\Ext\Form\Form();
            $objForm->set('contributorNome', $dadosContributor['name']);
            $objForm->set('contributorLogin', $dadosContributor['login']);
            $objForm->set('contributorId', $dadosContributor['id']);
            $objForm->set('contributorEmail', $dadosContributor['email']);
            $objForm->set('contributorAvatar', $dadosContributor['avatar_url']);
            $objForm->set('contributorUrl', $dadosContributor['html_url']);
            $objForm->set('contributorLocation', $dadosContributor['location']);
            $objForm->set('contributorUltimaAtualizacao', $dadosContributor['updated_at']);

            $campos = [
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
        $response   = $client->get($url);

        return \json_decode($response->getBody()->getContents(), true);
    }
}