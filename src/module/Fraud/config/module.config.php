<?php
namespace Fraud;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'controllers' => [
        'factories' => [
            Controller\FraudController::class => InvokableFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            'fraud' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/fraud[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\FraudController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'fraud' => __DIR__ . '/../view',
        ],
    ],
];