<?php
/**
 * Created by IntelliJ IDEA.
 * User: winglechen
 * Date: 16/3/17
 * Time: 23:55
 */

namespace Zan\Framework\Network\Tcp;

use Kdt\Iron\Nova\Nova;
use swoole_server as SwooleServer;
use Zan\Framework\Contract\Network\Response as BaseResponse;

class Response implements BaseResponse {
    /**
     * @var SwooleServer
     */
    private $swooleServer;
    private $request;

    public function __construct(SwooleServer $swooleServer, Request $request)
    {
        $this->swooleServer = $swooleServer;
        $this->request = $request;
    }

    public function getSwooleServer()
    {
        return $this->swooleServer;
    }

    public function end($content='')
    {
        if($content){
            $this->send($content);
        }

        $this->swooleServer->close($this->request->getFd());
    }

    public function sendException(\Exception $e)
    {
        $this->end($e);
    }

    public function send($content)
    {
        $serviceName = $this->request->getServiceName();
        $novaServiceName = $this->request->getNovaServiceName();
        $methodName  = $this->request->getMethodName();
        $content = Nova::encodeServiceOutput($novaServiceName, $methodName, $content);

        $remote = $this->request->getRemote();
        $outputBuffer = '';
        if (nova_encode($serviceName,
            $methodName,
            $remote['ip'],
            $remote['port'],
            $this->request->getSeqNo(),
            $this->request->getAttachData(),
            $content,
            $outputBuffer)) {


            $swooleServer = $this->getSwooleServer();
            $sendState = $swooleServer->send(
                $this->request->getFd(),
                $outputBuffer
            );
        }
    }

}