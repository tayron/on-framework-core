<?php
namespace Core\controller;

use Tayron\exceptions\MethodNotFoundException;
use Tayron\exceptions\ControllerNotFoundException;
use Tayron\exceptions\Exception;

use Tayron\Session;
use Tayron\RequestFacede;
use Tayron\Response;
use Tayron\Template;

use Tayron\ErrorHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Classe que gerencia as requisições, determina qual controller chamar, 
 * chama seu método e passa os parametros informados pelo cliente.
 * 
 * @author Tayron Miranda <dev@tayron.com.br>
 */
final class FrontController
{    
    /**
     * Armazena o local onde fica os templates
     * 
     * @var string
     */
    private $pathTemplate = null;
    
    /**
     * Armazena o local onde fica as views
     * 
     * @var string
     */    
    private $pathView = null;
    
    /**
     * Armazena o local onde fica os elementos da view
     * 
     * @var string
     */
    private $pathElement = null;
    
    /**
     * Armazena o namespace do controller default a ser invocado
     * 
     * @var string
     */
    protected $controller = '\Application\controller\IndexController';
    
    /**
     * Armazena o nome do método a ser invocado
     * 
     * @var string
     */
    protected $method = 'index';
    
    /**
     * FrontController::__construct
     * 
     * Recupera e seta o controller, action e parametros da requisições
     * @return void
     */
    public function __construct() 
    {
        // Setando o local onde fica os templates, view e elementos de view
        $this->pathTemplate = PATH . DS . 'src' . DS . 'view' . DS . 'template';
        $this->pathView = PATH . DS . 'src' . DS . 'view';
        $this->pathElement = PATH . DS . 'src' . DS . 'view' . DS . 'elements';
            
        try{
            $this->setErrorHandler();
            $requisicao = new RequestFacede();
            $parametros = explode('/', $requisicao->getUri());

            // Verificando se um controller foi informado
            if (isset($parametros[3]) && $parametros[3] != null) {
                $this->setController($parametros[3]);
            }

            // Verificando se uma action foi informado
            $action = end($parametros);
            
            if($action && $action !== $parametros[3]){
                $this->setMethod($action);
            }
        
        } catch(ControllerNotFoundException $ex){
            $this->gravarLog('ControllerNotFoundException', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());
        } catch(MethodNotFoundException $ex){
            $this->gravarLog('MethodNotFoundException', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());            
        } catch (\Exception $ex){
            $this->gravarLog('Exception', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());
        }
    }
	
    /**
     * FrontController::setErrorHandler
     * 
     * Método que seta carrega para a memória o manipulador de erro
     * 
     * @return void
     */
	private function setErrorHandler()
	{
		ErrorHandler::getInstance();
	}
    
    /**
     * FrontController::setController
     * 
     * Método que seta o nome do controller a ser carregado
     * 
     * @param string $nomeController - Nome da classe controller
     * @throws ControllerNotFoundException
     * @return void
     */
    private function setController($nomeController) 
    { 
        $nomeControllerTratado = ucfirst($this->tratarNomeControllerAction($nomeController));        
        $nomeComNamespaceController = "\Application\controller\\{$nomeControllerTratado}Controller";

        if (!class_exists($nomeComNamespaceController)) {
            throw new ControllerNotFoundException($nomeComNamespaceController);
        }
        $this->controller = $nomeComNamespaceController;
    }

    /**
     * FrontController::setMethod
     * 
     * Método que seta o nome do método a ser carregado
     * 
     * @param string $methodName Nome do método do controller a ser chamado
     * @throws MethodNotFoundException
     * @return void
     */
    private function setMethod($methodName) 
    {
        $method = $this->tratarNomeControllerAction($methodName);
		$this->verificarExistenciaMetodo($method);        
        $this->method = $method;
    }
    
    /**
     * FrontController::tratarNomeControllerAction
     * 
     * Método que trata nomes de controller e view
     * 
     * @param string $nome - Nome do controller ou action
     * @return string - Nome do controller ou action tratado
     */
    private function tratarNomeControllerAction($nome)
    {
        $parametros = explode('-', $nome);
        $nomeTratado = null;
        
        if(count($parametros) > 1){            
            $count = 0;
            foreach($parametros as $nome){                
                $nomeTratado .= ($count > 0) ? ucfirst($nome) : strtolower($nome);                
                $count++;
            }
        }else{
            $nomeTratado = strtolower(current($parametros));
        }
        
        return $nomeTratado;
    }
    
    /**
     * FrontController::run
     * 
     * Método que executa a requisições feita pelo usuÃ¡rio, executando um determinado
     * método de um controller.
     * 
     * @throws MethodNotFoundException
     * @return void
     */
    final public function run() 
    {
        $this->verificarExistenciaMetodo($this->method);

        $controller = new $this->controller(
            Session::getInstance(), 
            new RequestFacede(), 
            Response::getInstance(),
            Template::getInstance($this->pathView, $this->pathTemplate, $this->pathElement, 
                $this->controller, new RequestFacede())
        );

        try{
            call_user_func_array(array($controller, $this->method), array());  
        } catch(MethodNotFoundException $ex){
            $this->gravarLog('MethodNotFoundException', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());             
        } catch (Exception $ex){            
            $this->gravarLog('Exception', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());            
        } catch (\Exception $ex){
            $this->gravarLog('Exception', $ex->getMessage(), $ex->getTrace());
            $this->exibirMensagemErro($ex->getMessage());
        }        
    }    
    
	/**
	 * FrontController::verificarExistenciaMetodo
	 * 
	 * Verifica se o método existe no controller a ser carregado e lança exceção em caso de erro
	 * 
	 * @throws MethodNotFoundException
	 * @return void
	 */
	private function verificarExistenciaMetodo($metodo)
	{
		$controller = new \ReflectionClass($this->controller);
		if (!$controller->hasMethod($metodo)) {
			throw new MethodNotFoundException($metodo, $this->controller);
		}
	}
    
    /**
     * FrontController::gravarLog
     * 
     * Grava um log com mensagem de erro
     * 
     * @param string $nomeExcecao Nome da exceção 
     * @param string $mensagem Mensagem de erro
     * @param string $trace Caminho até onde o erro foi disparado
     * 
     * @return void
     */
    private function gravarLog($nomeExcecao, $mensagem, $trace)
    {
        $logger = new Logger($nomeExcecao);
        $logger->pushHandler(new StreamHandler("logs/{$nomeExcecao}.log", Logger::WARNING));
        $logger->addError(strip_tags(str_replace('<br />', ' | ', $mensagem)), $trace);                     
    }
    
	/**
	 * FrontController::exibirMensagemErro
	 * 
	 * Renderia um template e exibe a mensagem de erro ao usuário
     * 
     * @param string $mensagem Mensagem de erro
	 * 
	 * @return void
	 */    
    private function exibirMensagemErro($mensagem)
    {
        $template = Template::getInstance($this->pathView, $this->pathTemplate, 
            $this->pathElement, null, new RequestFacede());

        $template->setTemplate('error');
        $template->setParameters(array('mensagem' => $mensagem));        
        $template->render('exceptions/error');         
    }
}