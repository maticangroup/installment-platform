<?php

namespace App\Controller\General;


use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/general/menu", name="general_menu")
 */
class MenuController extends AbstractController
{
    /**
     * @Route("/side_menu", name="_side_menu")
     */
    public function sideMenu()
    {
        $serverName = $_SERVER['SERVER_NAME'];
        $serverPort = $_SERVER['SERVER_PORT'];
        $requestURI = $_SERVER['REQUEST_URI'];

        $sideMenu = [
            //Accounting
            [
                'main_menu_name' => 'حسابداری',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'کوپون',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/coupon-group/list',
                                'action' => ServerPermissions::accounting_coupongroup_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/coupon-group/create',
                                'action' => ServerPermissions::accounting_coupongroup_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'کارت هدیه',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/gift-card-group/list',
                                'action' => ServerPermissions::accounting_giftcardgroup_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/gift-card-group/create',
                                'action' => ServerPermissions::accounting_giftcardgroup_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'صورتحساب',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/invoice/list',
                                'action' => ServerPermissions::accounting_invoice_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/accounting/invoice/create',
                                'action' => ServerPermissions::accounting_invoice_new
                            ],
                        ]

                    ],
                ]


            ],
            //Accounting

            //CRM
            [
                'main_menu_name' => 'مدیریت مشتریان',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'گروه های مشتریان',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/crm/customer-group/create',
                                'action' => ServerPermissions::crm_customergroup_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'مشتری',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/crm/customer/list',
                                'action' => ServerPermissions::crm_customergroup_all
                            ],
                        ]

                    ],
                ]
            ],
            //CRM

            //Delivery
            [
                'main_menu_name' => 'پیک و مرسوله ها',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'روش های ارسال',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/delivery-method/list',
                                'action' => ServerPermissions::delivery_deliverymethod_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/delivery-method/create',
                                'action' => ServerPermissions::delivery_deliverymethod_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'پیک',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/delivery-person/list',
                                'action' => ServerPermissions::delivery_deliveryperson_all
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'مرسوله',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/dispatch/list',
                                'action' => ServerPermissions::delivery_dispatch_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/dispatch/create',
                                'action' => ServerPermissions::delivery_dispatch_new
                            ],
                        ]

                    ],
                ]
            ],
            //Delivery

            //Inventory
            [
                'main_menu_name' => 'انبار و قفسه ها',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'انبار',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/list',
                                'action' => ServerPermissions::inventory_inventory_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/create',
                                'action' => ServerPermissions::inventory_inventory_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'قفسه',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/shelve/list',
                                'action' => ServerPermissions::inventory_shelve_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/shelve/create',
                                'action' => ServerPermissions::inventory_shelve_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'سند',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/deed/list',
                                'action' => ServerPermissions::inventory_transferdeed_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/deed/create',
                                'action' => ServerPermissions::inventory_transferdeed_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'موجودی',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/inventory/stock/list',
                                'action' => ServerPermissions::inventory_inventory_all_item_products
                            ],
                        ]

                    ],
                ]
            ],
            //Inventory

            //Notification
            [
                'main_menu_name' => 'اطلاع رسانی ها',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'قالب های ایمیل',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/delivery/delivery-method/list',
                                'action' => ServerPermissions::delivery_deliverymethod_all
                            ],
                        ]

                    ],
                ]
            ],
            //Notification

            //Repository
            [
                'main_menu_name' => 'تعاریف',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'برند',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/brand/create',
                                'action' => ServerPermissions::repository_brand_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'شرکت',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/company/list',
                                'action' => ServerPermissions::repository_company_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/company/create',
                                'action' => ServerPermissions::repository_company_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'کلمات ممنوعه',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/forbidden-words/create',
                                'action' => ServerPermissions::repository_forbiddenwords_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'گارانتی',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/guarantee/create',
                                'action' => ServerPermissions::repository_guarantee_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'مدت های گارانتی',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/guarantee-duration/create',
                                'action' => ServerPermissions::repository_guarantee_add_duration
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'تامین کنندگان گارانتی',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/guarantee-provider/create',
                                'action' => ServerPermissions::repository_guarantee_add_provider
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'رنگ ها',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/color/create',
                                'action' => ServerPermissions::repository_color_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'روز های تعطیل',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/off-day/create',
                                'action' => ServerPermissions::repository_offdays_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'افراد',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/person/list',
                                'action' => ServerPermissions::repository_person_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/person/create',
                                'action' => ServerPermissions::repository_person_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'سایز',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/size/create',
                                'action' => ServerPermissions::repository_size_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'گروه مشحصات',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/spec-group/create',
                                'action' => ServerPermissions::repository_specgroup_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'دسته بندی ها',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/item-category/create',
                                'action' => ServerPermissions::repository_itemcategory_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'آیتم',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/item/list',
                                'action' => ServerPermissions::repository_item_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/repository/item/create',
                                'action' => ServerPermissions::repository_item_new
                            ],
                        ]

                    ],
                ]
            ],
            //Repository

            //Sale
            [
                'main_menu_name' => 'فروش',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'گروه های پیشنهادی',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/offer-group/create',
                                'action' => ServerPermissions::sale_offergroup_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'سند های قیمت گذاری',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/pricing-deed/list',
                                'action' => ServerPermissions::sale_pricingdeed_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/pricing-deed/create',
                                'action' => ServerPermissions::sale_pricingdeed_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'سفارش',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/order/list',
                                'action' => ServerPermissions::sale_order_all
                            ],
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/order/create',
                                'action' => ServerPermissions::sale_order_new
                            ],
                        ]

                    ],
                ]
            ],
            //Sale

            //Ticketing
            [
                'main_menu_name' => 'نظرات',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'کامنت',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/sale/offer-group/create',
                                'action' => ServerPermissions::sale_offergroup_new
                            ],
                        ]

                    ],
                ]
            ],
            //Ticketing

            //Authentication
            [
                'main_menu_name' => 'هویت سنجی',
                'main_menu_items' => [
                    [
                        'menu_item_name' => 'کلاینت',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'ایجاد',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/authentication/client/create',
                                'action' => ServerPermissions::authentication_client_new
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'نقش ها',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/authentication/role/create',
                                'action' => ServerPermissions::authentication_role_all
                            ],
                        ]

                    ],
                    [
                        'menu_item_name' => 'کاربران',
                        'menu_item_icon' => 'fa fa-th',
                        'menu_item_children' => [
                            [
                                'menu_child_name' => 'لیست',
                                'menu_child_link' => 'http://' . $serverName .':'. $serverPort . '/authentication/user/list',
                                'action' => ServerPermissions::authentication_user_all
                            ],
                        ]

                    ],
                ]
            ],
            //Authentication

        ];
        $authorizedMenu = [];
        foreach ($sideMenu as $menuNumber => $menu) {
            foreach ($menu['main_menu_items'] as $sectionNumber => $sections) {
                foreach ($sections['menu_item_children'] as $childNumber => $section) {
                    if (!AuthUser::if_is_allowed($section['action'])) {
                        unset($sideMenu[$menuNumber]['main_menu_items'][$sectionNumber]['menu_item_children'][$childNumber]);
                    }
                    if (count($sideMenu[$menuNumber]['main_menu_items'][$sectionNumber]['menu_item_children']) == 0) {
                        unset($sideMenu[$menuNumber]['main_menu_items'][$sectionNumber]);
                    }
                    if (count($sideMenu[$menuNumber]['main_menu_items']) == 0) {
                        unset($sideMenu[$menuNumber]);
                    }
                }
            }
        }


        $currentULR = 'http://' . $serverName .':'. $serverPort . ':' . $serverPort . $requestURI;

        return $this->render('general/menu/side-menu.html.twig', [
            'controller_name' => 'MenuController',
            'sideMenu' => $sideMenu,
            'currentULR' => $currentULR,

        ]);
    }
}
