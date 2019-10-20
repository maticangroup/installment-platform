<?php
/**
 * Created by PhpStorm.
 * User: Amirhossein
 * Date: 7/20/2019
 * Time: 11:15 AM
 */

namespace App\General;


class AdminSideMenu
{

    public static function get()
    {
        $menu = [
            [
                'main_menu_name' => 'Accounting',
                'main_menu_link' => '#',
                'main_menu_items' =>
                    [
                        'menu_item_name' => 'Coupon Group',
                        'menu_item_link' => '',
                        'menu_item_icon' => '',
                        'menu_item_children' =>
                            [
                                'menu_child_name' => 'List',
                                'menu_child_link' => 'http://127.0.0.1:8000/accounting/coupon-group/list',
                            ],
                        [
                            'menu_child_name' => 'Create',
                            'menu_child_link' => 'http://127.0.0.1:8000/accounting/coupon-group/create',
                        ],
                    ],
                [
                    'menu_item_name' => 'Gift Card Group',
                    'menu_item_link' => '',
                    'menu_item_icon' => '',
                    'menu_item_children' =>
                        [
                            'menu_child_name' => 'List',
                            'menu_child_link' => 'http://127.0.0.1:8000/accounting/gift-card-group/list',
                        ],
                    [
                        'menu_child_name' => 'Create',
                        'menu_child_link' => 'http://127.0.0.1:8000/accounting/gift-card-group/create',
                    ],
                ],
                [
                    'menu_item_name' => 'Invoice',
                    'menu_item_link' => '',
                    'menu_item_icon' => '',
                    'menu_item_children' =>
                        [
                            'menu_child_name' => 'List',
                            'menu_child_link' => 'http://127.0.0.1:8000/accounting/invoice/list',
                        ],
                    [
                        'menu_child_name' => 'Create',
                        'menu_child_link' => 'http://127.0.0.1:8000/accounting/invoice/create',
                    ],
                ],

            ],
        ];
    }

}