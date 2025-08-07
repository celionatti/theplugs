<?php

declare(strict_types=1);

namespace Plugs\Controller;

use Plugs\View\View;
use Plugs\Http\Response\Response;
use Plugs\Exceptions\Controller\ValidationException;

trait ControllerHelpers
{
    protected function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        return (new Response())->setContent($content)
                             ->setStatusCode($status)
                             ->setHeaders($headers);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return (new Response())->json($data, $status);
    }

    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $view = View::make($template, $data);
        $content = $view->render();
        return $this->response($content, $status, ['Content-Type' => 'text/html']);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return $this->response('', $status, ['Location' => $url]);
    }

    protected function abort(int $status = 404, string $message = ''): never
    {
        http_response_code($status);
        if ($message) {
            echo $message;
        }
        exit;
    }

    protected function validate(array $rules, array $messages = []): array
    {
        // Simple validation implementation
        $data = $this->request->all();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            $value = $data[$field] ?? null;

            foreach ($ruleList as $singleRule) {
                $ruleName = explode(':', $singleRule)[0];
                $ruleParams = explode(':', $singleRule)[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;
                    case 'min':
                        if (strlen($value) < (int)$ruleParams) {
                            $errors[$field][] = "The {$field} must be at least {$ruleParams} characters.";
                        }
                        break;
                    case 'max':
                        if (strlen($value) > (int)$ruleParams) {
                            $errors[$field][] = "The {$field} may not be greater than {$ruleParams} characters.";
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $data;
    }
}