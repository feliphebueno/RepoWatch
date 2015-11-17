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

class RepoWatchController extends Controller
{

    private $class;

    private $gitHubSecret = 'acb05a459790bf08035fc8c0b403c814a8443f93';
    
    public function __construct()
    {
        $this->class    = new RepoWatchClass();
    } 
    
    protected function iniciar()
    {
        $retorno = [];
        
        $payload = \json_decode(\file_get_contents('php://input'), true);

        print_r(\filter_input_array(\INPUT_SERVER));
        
        try {
            $this->class->processaWebHook($payload);
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
}
