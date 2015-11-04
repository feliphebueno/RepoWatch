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

class RepoWatchClass extends RepoWatchSql
{
    
    private $chavePrimaria;
    private $tabela;
    private $precedencia;
    private $colunas;
    private $banco;
    protected $con;
    
    public function __construct($cod = '')
    {

        $this->cod = $cod;
        $this->con = Conexao::conectar();
        
        parent::__construct();

        $this->tabela           = 'documento';        
        $this->precedencia      = 'documento';                   
        $this->chavePrimaria    = $this->precedencia . 'Cod';
        $this->colunasCrud = [
                                         'organogramaCod',
                                         'usuarioCod',
                                         'id',
                    $this->precedencia . 'Titulo',
                    $this->precedencia . 'Conteudo',
                    $this->precedencia . 'Data',
                    $this->precedencia . 'Privado',
                    $this->precedencia . 'Status'
        ];

    }   

    public function cadastrar($objForm, $confs)
    {
        $crudUtil = new CrudUtil($this->banco);
        $id = \bin2hex(\openssl_random_pseudo_bytes(20));
        $objForm->set('id', $id);
        $objForm->set('usuarioCod', $_SESSION['usuarioCod']);
        $objForm->set('documentoPrivado', 'N');
        $objForm->set('documentoStatus', 'A');

        $documentoCod = $crudUtil->insert($this->tabela, $this->colunasCrud, $objForm);
        
        if(\is_array($confs)){
            $this->setVinculoDocumento($documentoCod, $objForm, $confs);
        }
        
        return [
            'cod'   => $documentoCod,
            'id'    => $id
        ];
    }
    
    public function alterar($objForm)
    {
        $crudUtil = new CrudUtil($this->banco);

        return $crudUtil->update($this->tabela, ['documentoTitulo', 'documentoConteudo'], $objForm, ['id' => $objForm->get('idVersao')], ['id', 'organogramaCod']);
    }
    
    public function setVinculoDocumento($documentoCod, $objForm, $confs)
    {
        if(isset($confs['t']) and isset($confs['campos']) and \is_array($confs['campos'])){
            
            $crudUtil = new CrudUtil($this->banco);
            
            $tabela = $confs['t'];
            $campos = $confs['campos'];
            $camposBD = [];

            $campos['documentoCod'] = $documentoCod;
            
            if(isset($campos['cod'])){
                $campos['pessoaFisicaCod'] = $campos['cod'];
                unset($campos['cod']);
            }
            
            if(isset($campos['id'])){
                $campos['pessoaFisicaServidorCod'] = $campos['id'];
                unset($campos['id']);
            }

            foreach($campos as $key => $val){
                $objForm->set($key, $val);
                \array_push($camposBD, $key);
            }

            $crudUtil->insert($tabela, $camposBD, $objForm, ['organogramaCod']);
        }

        return;
    }
    
    public function iniciaVersaoDocumento($documentoTitulo, $documentoConteudo, $confs = [])
    {
        $objForm = (new \App\Ext\Form\Form());

        $objForm->set('documentoTitulo', $documentoTitulo);
        $objForm->set('documentoConteudo', $documentoConteudo);
        return $this->cadastrar($objForm, $confs);
    }

    public function getVersaoDocumento($id)
    {
        return $this->con->execLinha(parent::getVersaoDocumentoSql($id));
    }

    public function getUltimaVersaoByConfs($confs)
    {
        return $this->con->execLinha(parent::getUltimaVersaoByConfsSql($confs));
    }
    
    public function log($data)
    {
        $filename = 'log/'. \date('d-m-Y_H-i-s') .'.log';
        return \file_put_contents($filename, $data);
    }
}