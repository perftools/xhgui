<?php
class Xhgui_ErrorHandler
{
    /**
     * Register the default exception handler.
     *
     * @return void
     */
    public static function register()
    {
        set_exception_handler('Xhgui_ErrorHandler::handleError');
    }

    /**
     * Handle an exception
     *
     * @param Exception $exception
     * @return void
     */
    public static function handleError($exception)
    {
        $template = Xhgui_Template::load('error/view.twig');
        echo $template->render(array(
            'message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString()
        ));
    }
}
