<?php
namespace Core\controller;

use Tayron\exceptions\DatabaseConfigurationNotFoundException;
use \SessionHandlerInterface;
use Tayron\RequestInterface;
use Tayron\Response;
use Tayron\TemplateInterface;

/**
 * Classe que gerencia o carregamento dos templates e views
 *
 * @author Tayron Miranda <dev@tayron.com.br>
 */
class ApplicationController
{
    /**
     * Armazena objeto responsável por gerenciamento da sessão
     * 
     * @var Tayron\Session
     */
    protected $session = null;

    /**
     * Armazena objeto responsável por gerenciamento da requisições
     * 
     * @var Tayron\RequestInterface
     */
    private $request = null;

    /**
     * Armazena objeto responsável por gerenciamento da responsa ao cliente
     * 
     * @var Tayron\Response
     */
    private $response = null;
    
    /**
     * Armazena objeto responsável por gerenciamento do template
     * 
     * @var Tayron\TemplateInterface
     */
    private $template = null;

    /**
	 * ApplicationController::__construct
	 *
     * Cria e armazena uma sessão para ser usada na aplicação
     */
    public function __construct(SessionHandlerInterface $session, RequestInterface $request, 
        Response $response, TemplateInterface $template)
    {
        $this->session = $session;
        $this->request = $request;
        $this->response = $response;
        $this->template = $template;
        $this->loadingDatabaseConfiguration();
    }
    
    /**
     * ApplicationController::redirect
     * 
     * Método que redireciona para uma outra tela
     * 
     * @param array $controllerAction Lista com nome do controller e action
     * @return void
     */    
    final protected function redirect(array $controllerAction)
    {
        $this->request->redirect($controllerAction);
    }
    
    /**
	 * ApplicationController::getPostParameter
	 * 
     * Metodo que retorna os parametros enviados via POST
	 * 
	 * @param string $key Nome do parametro a ser recuperado
	 * @return mixed Valor aramazenado via POST
     */    
    final protected function getPostParameter($key = null)
    {        
        return $this->request->getPostParameter($key);
    }
    
    /**
	 * ApplicationController::getGetParameter
	 * 
     * Metodo que retorna os parametros enviados via GET
	 * 
	 * @param string $key Nome do parametro a ser recuperado
	 * @return mixed Valor aramazenado via GET
     */       
    final protected function getGetParameter($key = null)
    {
        return $this->request->getGetParameter($key);
    }
    
    /**
	 * ApplicationController::getPutParameter
	 * 
     * Metodo que retorna os parametros enviados via PUT
	 * 
	 * @param string $key Nome do parametro a ser recuperado
	 * @return mixed Valor aramazenado via PUT
     */       
    final protected function getPutParameter($key = null)
    {
        return $this->request->getPutParameter($key);
    }    
    
    /**
     * ApplicationController::requestIsPost
     *
     * Método que informa se a requisição feita foi via POST
     *
     * @return boolean Retorna true caso a requisição seja feita via POST
     */
    final protected function requestIsPost() 
    {
        return $this->request->isPost();
    }

    /**
     * ApplicationController::requestIsGet
     *
     * Método que informa se a requisição feita foi via GET
     *
     * @return boolean Retorna true caso a requisição seja feita via GET
     */
    final protected function requestIsGet() 
    {
        return $this->request->isGet();
    }
    
    /**
     * ApplicationController::requestIsDelete
     *
     * Método que informa se a requisição feita foi via DELETE
     *
     * @return boolean Retorna true caso a requisição seja feita via DELETE
     */
    final protected function requestIsDelete() 
    {
        return $this->request->isDelete();
    }    
    
    /**
     * ApplicationController::requestIsPut
     *
     * Método que informa se a requisição feita foi via PUT
     *
     * @return boolean Retorna true caso a requisição seja feita via PUT
     */
    final protected function requestIsPut() 
    {
        return $this->request->isPut();
    }    
    
    /**
     * ApplicationController::getUri
     * 
     * Método que retorna uma lista de parametros informados na uri
     * 
     * @return array Lista de parametros da requisições uri
     */	    
    final protected function getUri() 
    {
        return $this->request->getUri();
    }
    
    /**
     * ApplicationController:: setHeader
     *
     * Método que seta cabeçalho de resposta da página
     * 
     * @param string $value Mensagem de cabeçalho de resposta
     * @param boolean $replace True para substituir um cabeçalho já existente
     * @param int $httpResponseCode Código de resposta do servidor
     * 
     * @throws InvalidArgumentException
     * 
     * @exemple:
     *  setHeader("HTTP/1.0 404 Not Found")
     *  setHeader("Location: http://www.example.com/") - Redirect browser
     *  setHeader('WWW-Authenticate: Negotiate')
     *  setHeader('WWW-Authenticate: NTLM', false)
     *  setHeader('Content-Type: application/pdf')
     *  setHeader('Content-Disposition: attachment; filename="downloaded.pdf"')
     *  setHeader('Cache-Control: no-cache, must-revalidate'); - HTTP/1.1
     *  setHeader('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); Date in the past
     * 
     * @return void
     */    
    protected function setHeader($value, $replace = true, $httpResponseCode = null)
    {
        $this->response->setHeader($value, $replace, $httpResponseCode);
    }
    
    /**
     * ApplicationController::sendHeader
     * 
     * Método que envia os cabeçalhos de requisição
     * 
     * @return void
     */
    protected function sendHeader()
    {
        $this->response->display();
    }    

    /**
	 * ApplicationController::beforeRender
	 *
     * Método que executa conjunto de rotinas antes da tela ser renderizada
     * 
	 * @return void
     */          
    protected function beforeRender()
    {
        
    }
    
    /**
	 * ApplicationController::render
	 *
     * Método que seta a view e renderia no template
     * 
	 * @param string $view Nome da view
	 * @return void
     */    
    final protected function render($view)
    {
        $this->sendHeader();
        $this->beforeRender();
        $this->template->render($view);
        $this->afterRender();
    }
    
    /**
	 * ApplicationController::afterRender
	 *
     * Método que executa conjunto de rotinas logo após a tela ser renderizada
     * 
	 * @return void
     */      
    protected function afterRender()
    {
        
    }    
    
    /**
	 * ApplicationController::setTemplate
	 * 
	 * Método que informa qual template deverá ser usado
	 * 
	 * @param string $template Nome do template
	 * @return void
     */
    final protected function setTemplate($template)
    {
        $this->template->setTemplate($template);
    }    
    
    /**
     * ApplicationController::setParameters
     * 
     * Método que seta os parametros a ser utilizado nas views
     * 
     * @param array $parametros Lista com os parametros
     * @return void
     */    
    final protected function setParameters(array $parametros)
    {
        $this->template->setParameters($parametros);
    }

    /**
	 * ApplicationController::loadingDatabaseConfiguration
	 *
     * Carrega as configurações de conexão com banco de dados
	 * 
	 * @return void
     */
    private function loadingDatabaseConfiguration()
    {
        $arquivoConfiguracao = PATH . DS . 'src' . DS . 'config' . DS . 'database.php';

        if(!file_exists($arquivoConfiguracao)){
            throw new DatabaseConfigurationNotFoundException($arquivoConfiguracao);
        }
		
        require_once($arquivoConfiguracao);
    }
}