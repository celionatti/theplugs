<?php

declare(strict_types=1);

namespace Plugs\Controller;

use Plugs\View\View;
use Plugs\Http\Response\Response;
use Plugs\Exceptions\Http\HttpException;
use Plugs\Exceptions\Controller\ValidationException;

trait ControllerHelpers
{
    /**
     * Create a basic response
     */
    protected function response(
        mixed $content = '', 
        int $status = 200, 
        array $headers = []
    ): Response {
        return (new Response())
            ->setContent($content)
            ->setStatusCode($status)
            ->setHeaders($headers);
    }

    /**
     * Create a JSON response
     */
    protected function json(
        array $data, 
        int $status = 200, 
        array $headers = []
    ): Response {
        return $this->response('', $status, $headers)
            ->json($data);
    }

    /**
     * Create a successful API response
     */
    protected function success(
        mixed $data = null,
        string $message = '',
        int $status = 200,
        array $headers = []
    ): Response {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status, $headers);
    }

    /**
     * Create an error API response
     */
    protected function error(
        string $message = '',
        mixed $errors = null,
        int $status = 400,
        array $headers = []
    ): Response {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status, $headers);
    }

    /**
     * Create a view response
     */
    protected function view(
        string $template, 
        array $data = [], 
        int $status = 200,
        array $headers = []
    ): Response {
        $view = View::make($template, $data);
        $content = $view->render();
        
        return $this->response(
            $content, 
            $status, 
            array_merge(['Content-Type' => 'text/html'], $headers)
        );
    }

    /**
     * Create a redirect response
     */
    protected function redirect(
        string $url, 
        int $status = 302,
        array $headers = []
    ): Response {
        return $this->response('', $status, array_merge(['Location' => $url], $headers));
    }

    /**
     * Redirect back to previous URL
     */
    protected function back(array $with = []): Response
    {
        $url = $this->request->headers->get('Referer', '/');
        
        if (!empty($with)) {
            $this->withFlash($with);
        }
        
        return $this->redirect($url);
    }

    /**
     * Abort with an error response
     */
    protected function abort(
        int $status = 404, 
        string $message = ''
    ): never {
        throw new HttpException($status, $message);
    }

    /**
     * Set flash data in session
     */
    protected function withFlash(array $data): void
    {
        foreach ($data as $key => $value) {
            $_SESSION['flash'][$key] = $value;
        }
    }

    /**
     * Get CSRF token
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): void
    {
        $token = $this->request->get('_token') 
            ?: $this->request->headers->get('X-CSRF-TOKEN');
            
        if (empty($token) || !hash_equals($this->csrfToken(), $token)) {
            throw new HttpException(419, 'CSRF token mismatch');
        }
    }

    /**
     * Validate request data
     */
    protected function validate(
        array $rules, 
        array $messages = [],
        array $customAttributes = []
    ): array {
        $data = $this->request->all();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            $value = $data[$field] ?? null;
            $fieldName = $customAttributes[$field] ?? $field;

            foreach ($ruleList as $singleRule) {
                $ruleParts = explode(':', $singleRule, 2);
                $ruleName = $ruleParts[0];
                $ruleParams = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = $messages["{$field}.required"] 
                                ?? "The {$fieldName} field is required.";
                        }
                        break;
                        
                    case 'min':
                        $length = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : $value);
                        if ($length < (int)$ruleParams) {
                            $errors[$field][] = $messages["{$field}.min"] 
                                ?? "The {$fieldName} must be at least {$ruleParams} characters.";
                        }
                        break;
                        
                    case 'max':
                        $length = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : $value);
                        if ($length > (int)$ruleParams) {
                            $errors[$field][] = $messages["{$field}.max"] 
                                ?? "The {$fieldName} may not be greater than {$ruleParams} characters.";
                        }
                        break;
                        
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $messages["{$field}.email"] 
                                ?? "The {$fieldName} must be a valid email address.";
                        }
                        break;
                        
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = $messages["{$field}.numeric"] 
                                ?? "The {$fieldName} must be a number.";
                        }
                        break;
                        
                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field][] = $messages["{$field}.array"] 
                                ?? "The {$fieldName} must be an array.";
                        }
                        break;
                        
                    case 'in':
                        $allowedValues = explode(',', $ruleParams);
                        if (!in_array($value, $allowedValues)) {
                            $errors[$field][] = $messages["{$field}.in"] 
                                ?? "The selected {$fieldName} is invalid.";
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

    /**
     * Paginate data
     */
    protected function paginate(
        array $items,
        int $perPage = 15,
        ?int $currentPage = null,
        array $options = []
    ): array {
        $currentPage = $currentPage ?: (int) ($this->request->get('page') ?? 1);
        $offset = ($currentPage - 1) * $perPage;
        
        return [
            'data' => array_slice($items, $offset, $perPage),
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => count($items),
            'last_page' => (int) ceil(count($items) / $perPage),
        ];
    }
}