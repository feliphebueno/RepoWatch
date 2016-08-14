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

class RepoWatchSql
{

    protected $con;
    private $util;
    private $banco;

    public function __construct()
    {
        $this->con = Conexao::conectar('siprevcl_prod');
    }
    
    public function getContributorSql($userName)
    {
        $qb = $this->con->qb('siprevcl_prod');
        
        $qb->select('*')
           ->from('contributor')
           ->where($qb->expr()->eq('contributorLogin', ':contributorLogin'))
           ->setMaxResults(1)
           ->setParameter('contributorLogin', $userName, \PDO::PARAM_STR);

        return $qb;
    }
    
    public function getRepositorioSql($id)
    {
        $qb = $this->con->qb('siprevcl_prod');
        
        $qb->select('*')
           ->from('repositorio')
           ->where($qb->expr()->eq('repositorioId', ':id'))
           ->setMaxResults(1)
           ->setParameter('id', $id, \PDO::PARAM_STR);

        return $qb;
    }
    
    public function getPullRequestSql($id)
    {
        $qb = $this->con->qb('siprevcl_prod');
        
        $qb->select('*')
           ->from('repositorio_pull')
           ->where($qb->expr()->eq('repositorioPullId', ':repositorioPullId'))
           ->setMaxResults(1)
           ->setParameter('repositorioPullId', $id, \PDO::PARAM_STR);

        return $qb;
    }

    public function getIssueSql($id)
    {
        $qb = $this->con->qb('siprevcl_prod');
        
        $qb->select('*')
           ->from('repositorio_issue')
           ->where($qb->expr()->eq('repositorioIssueId', ':repositorioIssueId'))
           ->setMaxResults(1)
           ->setParameter('repositorioIssueId', $id, \PDO::PARAM_STR);

        return $qb;
    }

    public function getBranchSql($nome)
    {
        $qb = $this->con->qb('siprevcl_prod');
        
        $qb->select('*')
           ->from('repositorio_branch')
           ->where($qb->expr()->eq('repositorioBranchNome', ':nome'))
           ->setMaxResults(1)
           ->setParameter('nome', $nome, \PDO::PARAM_STR);

        return $qb;
    }

    public function getCommitSql($sha)
    {
        $qb = $this->con->qb('siprevcl_prod');

        $qb->select('*')
           ->from('repositorio_branch_commit')
           ->where($qb->expr()->eq('repositorioBranchCommitSha', ':sha'))
           ->setMaxResults(1)
           ->setParameter('sha', $sha, \PDO::PARAM_STR);

        return $qb;
    }
    
    public function verificaAssignedSql($contributorCod, $repositorioIssueCod)
    {
        $qb = $this->con->qb('siprevcl_prod');

        $qb->select('contributorCod')
           ->from('repositorio_issue_assigned')
           ->where($qb->expr()->eq('contributorCod', ':contributorCod'))
           ->andWhere($qb->expr()->eq('repositorioIssueCod', ':repositorioIssueCod'))
           ->setMaxResults(1)
           ->setParameter('repositorioIssueCod', $repositorioIssueCod, \PDO::PARAM_INT)
           ->setParameter('contributorCod', $contributorCod, \PDO::PARAM_INT);

        return $qb;
    }
}
