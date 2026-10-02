<?php
declare(strict_types=1);

defined('CFC_ROOT') || exit;

return [
    'site' => [
        'label' => 'Header & Footer',
        'path' => '',
        'groups' => [
            [
                'label' => 'Logos',
                'fields' => [
                    ['key' => 'logo_home', 'type' => 'image', 'label' => 'Home header logo (color)', 'default' => '2024/04/Logo.png'],
                    ['key' => 'logo_inner', 'type' => 'image', 'label' => 'Inner-page header logo (white)', 'default' => '2024/04/Whitelogo.png'],
                    ['key' => 'logo_footer', 'type' => 'image', 'label' => 'Footer logo', 'default' => '2024/04/Logo.png'],
                    ['key' => 'favicon', 'type' => 'image', 'label' => 'Favicon', 'default' => '2024/03/cropped-Fev-32x32.png'],
                    ['key' => 'og_image', 'type' => 'image', 'label' => 'Social share image', 'default' => '2024/04/Whitelogo.png'],
                ],
            ],
            [
                'label' => 'Navigation labels',
                'fields' => [
                    ['key' => 'nav_home', 'type' => 'text', 'label' => 'Home', 'default' => 'Home'],
                    ['key' => 'nav_about', 'type' => 'text', 'label' => 'About us', 'default' => 'About us'],
                    ['key' => 'nav_menu', 'type' => 'text', 'label' => 'Menu', 'default' => 'Menu'],
                    ['key' => 'nav_shop', 'type' => 'text', 'label' => 'Shop', 'default' => 'Shop'],
                    ['key' => 'nav_franchise', 'type' => 'text', 'label' => 'Franchise', 'default' => 'Franchise'],
                    ['key' => 'nav_blog', 'type' => 'text', 'label' => 'Blog', 'default' => 'Blog'],
                    ['key' => 'nav_gallery', 'type' => 'text', 'label' => 'Gallery', 'default' => 'Gallery'],
                    ['key' => 'nav_media', 'type' => 'text', 'label' => 'Media Hub', 'default' => 'Media Hub'],
                    ['key' => 'nav_contact', 'type' => 'text', 'label' => 'Contact', 'default' => 'Contact'],
                ],
            ],
            [
                'label' => 'Contact details',
                'fields' => [
                    ['key' => 'footer_tagline', 'type' => 'text', 'label' => 'Footer tagline', 'default' => 'Welcome To Authentic South Indian Filter Coffee Franchise'],
                    ['key' => 'email', 'type' => 'text', 'label' => 'Email', 'default' => 'chennapatnamfiltercoffee@gmail.com'],
                    ['key' => 'phone_display', 'type' => 'text', 'label' => 'Primary phone (hero display)', 'default' => '94573 09999'],
                    ['key' => 'phone_tel', 'type' => 'text', 'label' => 'Primary phone (hero tel:)', 'default' => '9457309999'],
                    ['key' => 'footer_phone', 'type' => 'text', 'label' => 'Footer phone 1 (display)', 'default' => '+919457309999'],
                    ['key' => 'footer_phone_tel', 'type' => 'text', 'label' => 'Footer phone 1 (tel:)', 'default' => '+919457309999'],
                    ['key' => 'phone_alt', 'type' => 'text', 'label' => 'Footer phone 2 (display)', 'default' => '+919467452222'],
                    ['key' => 'phone_alt_tel', 'type' => 'text', 'label' => 'Footer phone 2 (tel:)', 'default' => '+919467452222'],
                    ['key' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp number (digits only)', 'default' => '919457309999'],
                    ['key' => 'address', 'type' => 'textarea', 'label' => 'Corporate address', 'default' => 'Chennapatnam filter coffee, 4-2, Mouli towers, Near jyothi convention hall, Chandra mouli puram, Benz circle, Vijayawada, A.P, Pin : 520010'],
                ],
            ],
            [
                'label' => 'Footer headings',
                'fields' => [
                    ['key' => 'quick_heading', 'type' => 'text', 'label' => 'Quick Links heading', 'default' => 'Quick Links'],
                    ['key' => 'brands_heading', 'type' => 'text', 'label' => 'Other Brands heading', 'default' => 'Other Brands'],
                    ['key' => 'office_heading', 'type' => 'text', 'label' => 'Corporate Office heading', 'default' => 'Corporate Office'],
                ],
            ],
            [
                'label' => 'Custom code snippets',
                'fields' => [
                    ['key' => 'code_head', 'type' => 'code', 'label' => 'Header code (inside <head> — GTM, meta, CSS)', 'default' => ''],
                    ['key' => 'code_body', 'type' => 'code', 'label' => 'Body code (after <body> — GTM noscript, pixels)', 'default' => ''],
                    ['key' => 'code_footer', 'type' => 'code', 'label' => 'Footer code (before </body> — scripts, chat widgets)', 'default' => ''],
                ],
            ],
            [
                'label' => 'Franchise PDFs',
                'fields' => [
                    ['key' => 'pdf_franchise', 'type' => 'file', 'label' => 'Franchise details PDF', 'default' => '2024/04/CFC-Franchise.pdf'],
                    ['key' => 'pdf_presentation', 'type' => 'file', 'label' => 'Franchise outlet presentation PDF', 'default' => 'pdfs/CFC-Outlet-Presentation.pdf'],
                    ['key' => 'pdf_3d', 'type' => 'file', 'label' => 'Franchise 3D views PDF', 'default' => '2024/04/CHENNAPATNAM-FILTER-COFFEE-3D-VIEWS.pdf'],
                    ['key' => 'pdf_franchise_label', 'type' => 'text', 'label' => 'Franchise details button (links to Franchise page)', 'default' => 'Franchise Details'],
                    ['key' => 'pdf_presentation_label', 'type' => 'text', 'label' => 'Outlet presentation button label', 'default' => 'Franchise Outlet Presentation'],
                    ['key' => 'pdf_3d_label', 'type' => 'text', 'label' => '3D PDF button label', 'default' => 'Franchise 3D View PDF'],
                ],
            ],
        ],
        'repeaters' => [
            'social' => [
                'label' => 'Social links',
                'add' => 'Add social link',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Name'],
                    ['key' => 'url', 'type' => 'url', 'label' => 'URL'],
                ],
                'default' => [
                    ['label' => 'Instagram', 'url' => 'https://www.instagram.com/chennapatnamfiltercoffee?igsh=bWpxeHd6Z3k4aXVu&utm_source=qr'],
                    ['label' => 'Pinterest', 'url' => 'https://in.pinterest.com/chennapatnamfiltercoffee/'],
                    ['label' => 'Facebook', 'url' => 'https://www.facebook.com/share/qRhPbopzaAPiy6sg/?mibextid=LQQJ4d'],
                    ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/chennapatnamfiltercoffee/'],
                    ['label' => 'X', 'url' => 'https://x.com/Chennapatnamfc'],
                    ['label' => 'YouTube', 'url' => 'https://www.youtube.com/@Chennapatnamfiltercoffee'],
                ],
            ],
            'footer_brands' => [
                'label' => 'Other brands (footer)',
                'add' => 'Add brand',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Name'],
                    ['key' => 'url', 'type' => 'url', 'label' => 'URL (optional)'],
                ],
                'default' => [
                    ['label' => 'Andaal Home Foods', 'url' => 'https://www.andaalhomefoods.com/'],
                    ['label' => 'Chai Macha', 'url' => 'https://chaimacha.com/'],
                    ['label' => 'Oasis Kitchen', 'url' => 'https://www.instagram.com/oasis.kitchens/'],
                    ['label' => 'Shakers & Movers', 'url' => 'https://www.shakersandmovers.in/'],
                    ['label' => 'Macha Dhaba', 'url' => 'https://www.instagram.com/macha_dhaba/'],
                    ['label' => 'Ullikaram Pesarattu', 'url' => 'https://www.instagram.com/ullikaram_pesarattu/'],
                    ['label' => 'Kavali Atlu Kodi Pulusu', 'url' => 'https://www.instagram.com/kavaliatlukodipulusu/'],
                ],
            ],
        ],
    ],

    'home' => [
        'label' => 'Home',
        'path' => '/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Chennapatnam Authentic South Indian Filter Coffee Brand'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Hero',
                'fields' => [
                    ['key' => 'hero_welcome', 'type' => 'text', 'label' => 'Welcome line', 'default' => 'Welcome to'],
                    ['key' => 'hero_logo', 'type' => 'image', 'label' => 'Hero wordmark', 'default' => '2024/04/Logo-copy-1.png'],
                    ['key' => 'hero_call', 'type' => 'text', 'label' => 'Call heading', 'default' => 'For Franchise call'],
                ],
            ],
            [
                'label' => 'About teaser',
                'fields' => [
                    ['key' => 'intro_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'About us'],
                    ['key' => 'intro_p1', 'type' => 'textarea', 'label' => 'Paragraph 1', 'default' => 'Welcome to Chennapatnam Filter Coffee, Where tradition meets your taste buds.'],
                    ['key' => 'intro_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Come fall in love with our rich heritage and authentic South Indian Filter Coffee.'],
                ],
            ],
            [
                'label' => 'Andaal story',
                'fields' => [
                    ['key' => 'andal_image', 'type' => 'image', 'label' => 'Illustration', 'default' => '2024/03/aaa-3.png'],
                    ['key' => 'andal_p1', 'type' => 'textarea', 'label' => 'Paragraph 1', 'default' => 'Before knowing us better, let us introduce a fascinating story of our grandmother called Andaal. In the heart of Chennapatnam, there lived an old lady named Andaal. Nestled in the warmth of her ancestral home, Andaal held the age-old secret recipe of an extraordinary coffee blend passed down through generations. She welcomed everyone warmly and used to offer a cup of her meticulously crafted coffee. As the aromatic brew danced on their taste buds, the guests couldn’t help but applaud Andaal’s unparalleled recipe. Andaal, with her own style and precision, captivated the hearts of all who visited.'],
                    ['key' => 'andal_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Today, Chennapatnam Filter Coffee pays homage to Andaal’s legacy. We continue her tradition, Blending South Indian Filter Coffee Tradition with Innovation the finest beans with the same meticulous care, ensuring that every cup holds the magic of Andaal’s secret recipe.'],
                    ['key' => 'andal_p3', 'type' => 'textarea', 'label' => 'Paragraph 3', 'default' => 'Join us for “Coffee with Andaal”. Legacy continues…'],
                ],
            ],
            [
                'label' => 'Vision quote',
                'fields' => [
                    ['key' => 'quote_image', 'type' => 'image', 'label' => 'Dabara illustration', 'default' => '2024/03/Group-8.png'],
                    ['key' => 'quote_text', 'type' => 'textarea', 'label' => 'Quote (line break = new line)', 'default' => "\" Vision is the Art of seeing \nwhat is invisible to others. ''"],
                ],
            ],
            [
                'label' => 'About Founder',
                'fields' => [
                    ['key' => 'founder_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'About Founder'],
                    ['key' => 'founder_p1', 'type' => 'textarea', 'label' => 'Paragraph 1', 'default' => 'Karthik Chidipothu graduated in Civil Engineering and did his Masters in Business Administration from the UK and is basically from a construction background. Being the managing partner of Krishna Constructions, he has vast experience in the Road construction industry.'],
                    ['key' => 'founder_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Being an F&B enthusiast with passion, in the year 2019 he entered into coffee outlets and started brands with a vision of delivering authentic traditional coffee with exceptional taste at affordable prices. With this formula, within a very short time, it got an immense customer response.'],
                    ['key' => 'founder_image', 'type' => 'image', 'label' => 'Founder photo', 'default' => '2025/11/About.webp'],
                    ['key' => 'founder_name', 'type' => 'text', 'label' => 'Name', 'default' => 'Karthik Chidipothu'],
                    ['key' => 'founder_role', 'type' => 'text', 'label' => 'Role', 'default' => 'Founder & Managing Director'],
                    ['key' => 'founder_p3', 'type' => 'textarea', 'label' => 'Paragraph 3', 'default' => 'Eventually, with widespread outlets and food courts across the states, including highways & a few medical colleges, the business went widespread to nearly 150+ outlets on his own. With all his expertise in this concept, then Karthik started giving franchises in order to spread the delightful coffee experience to every corner of the country.'],
                    ['key' => 'founder_p4', 'type' => 'textarea', 'label' => 'Paragraph 4', 'default' => 'Karthik’s vision is to ensure our decades of authentic filter coffee accessibility to the next generation by offering premium quality products at an affordable cost to ordinary people. Hence, this has led to one of the leading and largest coffee chains in India. In recognition of his excellence and efforts, he was awarded The Times of India Business award in 2021 & with ” Young entrepreneur award” by Sun Network in 2022.'],
                ],
            ],
            [
                'label' => 'Our Story',
                'fields' => [
                    ['key' => 'story_image', 'type' => 'image', 'label' => 'Story illustration', 'default' => '2024/03/aaa-2.png'],
                    ['key' => 'story_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Our Story'],
                    ['key' => 'story_sub', 'type' => 'text', 'label' => 'Subheading', 'default' => 'Blending the Tradition with Innovation'],
                    ['key' => 'story_p1', 'type' => 'textarea', 'label' => 'Intro paragraph', 'default' => 'At Chennapatnam Filter Coffee, we offer you more than just a cup of coffee; we present the story of a fascinating taste and the cultural legacy of South India.'],
                    ['key' => 'story_days_heading', 'type' => 'text', 'label' => 'Second heading', 'default' => 'In those days there was Coffee'],
                    ['key' => 'story_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Our Grandmothers days were golden days for coffee in South India. They were the masters at crafting the perfect brew with care. The rich aroma and the exceptional taste were timeless. When our relatives visited our homes, they were generously offered freshly brewed coffee. In those cherished moments, coffee was not just an offering, it was an integral part of our lives. However, the times have changed, and the attraction towards instant coffees has departed our souls from the sensory experience of the perfect traditional brew. The aroma and the taste that once defined our coffee culture are now fading, leaving us with memorable moments.'],
                    ['key' => 'story_p3', 'type' => 'textarea', 'label' => 'Paragraph 3', 'default' => 'Our mission at Chennapatnam Filter Coffee is to revive those golden days, to bring back the essence of South Indian Coffee traditions.'],
                    ['key' => 'story_p4', 'type' => 'textarea', 'label' => 'Paragraph 4', 'default' => 'We invite you to join us on this journey, where every cup is a tribute to the rich heritage and flavors that have filled our homes for generations.'],
                    ['key' => 'story_p5', 'type' => 'textarea', 'label' => 'Paragraph 5', 'default' => 'Together, let us rediscover the magic of the perfect traditional Coffee.'],
                ],
            ],
            [
                'label' => 'Franchise CTA',
                'fields' => [
                    ['key' => 'cta_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Welcome To India\'s Best Filter Coffee Franchise'],
                    ['key' => 'cta_p1', 'type' => 'textarea', 'label' => 'Paragraph 1', 'default' => 'Are you ready to be a part of the thriving coffee culture and own a successful business?'],
                    ['key' => 'cta_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Chennapatnam Filter Coffee offers a golden opportunity for passionate entrepreneurs to join our family and become proud owners of a unique and profitable coffee franchise.'],
                    ['key' => 'cta_for', 'type' => 'text', 'label' => 'Phone heading', 'default' => 'For Franchise'],
                    ['key' => 'cta_read_more', 'type' => 'text', 'label' => 'Read more label', 'default' => 'Read More..'],
                ],
            ],
            [
                'label' => 'Bottom images',
                'fields' => [
                    ['key' => 'wide_image', 'type' => 'image', 'label' => 'Wide outlet photo', 'default' => '2024/03/ggg-1.png'],
                    ['key' => 'strip_image', 'type' => 'image', 'label' => 'Cup strip', 'default' => '2024/03/tb-1.png'],
                ],
            ],
        ],
    ],

    'about' => [
        'label' => 'About us',
        'path' => 'about-us/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => "Our Story | Traditional Filter Coffee From Andaal's Kitchen"],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Map video',
                'fields' => [
                    ['key' => 'map_video', 'type' => 'file', 'label' => 'Map video (mp4)', 'default' => '2026/04/map.mp4'],
                ],
            ],
            [
                'label' => 'Our Story',
                'fields' => [
                    ['key' => 'story_image', 'type' => 'image', 'label' => 'Story photo', 'default' => '2024/11/IMG-20241106-WA0008.png'],
                    ['key' => 'story_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Our Story'],
                    ['key' => 'story_sub', 'type' => 'text', 'label' => 'Subheading', 'default' => 'Blending the Tradition with Innovation'],
                    ['key' => 'story_p1', 'type' => 'textarea', 'label' => 'Intro paragraph', 'default' => 'At Chennapatnam Filter Coffee, we offer you more than just a cup of coffee; we present the story of a fascinating taste and the cultural legacy of South India.'],
                    ['key' => 'story_days_heading', 'type' => 'text', 'label' => 'Second heading', 'default' => 'In those days there was Coffee'],
                    ['key' => 'story_p2', 'type' => 'textarea', 'label' => 'Paragraph 2', 'default' => 'Our Grandmothers days were golden days for coffee in South India. They were the masters at crafting the perfect brew with care. The rich aroma and the exceptional taste were timeless. When our relatives visited our homes, they were generously offered freshly brewed coffee. In those cherished moments, coffee was not just an offering, it was an integral part of our lives. However, the times have changed, and the attraction towards instant coffees has departed our souls from the sensory experience of the perfect traditional brew. The aroma and the taste that once defined our coffee culture are now fading, leaving us with memorable moments.'],
                    ['key' => 'story_p3', 'type' => 'textarea', 'label' => 'Paragraph 3', 'default' => 'Our mission at Chennapatnam Filter Coffee is to revive those golden days, to bring back the essence of South Indian Coffee traditions.'],
                    ['key' => 'story_p4', 'type' => 'textarea', 'label' => 'Paragraph 4', 'default' => 'We invite you to join us on this journey, where every cup is a tribute to the rich heritage and flavors that have filled our homes for generations.'],
                    ['key' => 'story_p5', 'type' => 'textarea', 'label' => 'Paragraph 5', 'default' => 'Together, let us rediscover the magic of the perfect traditional Coffee.'],
                ],
            ],
            [
                'label' => 'Other brands heading',
                'fields' => [
                    ['key' => 'brands_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Other brands'],
                ],
            ],
            [
                'label' => 'Entrepreneurial Journey',
                'fields' => [
                    ['key' => 'journey_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Entrepreneurial Journey'],
                    ['key' => 'journey_lead', 'type' => 'textarea', 'label' => 'Lead paragraph', 'default' => 'Remarkable achievements of Karthik Chidipothu, a visionary entrepreneur redefining the F&B industry with innovation, authenticity, and excellence through his award-winning coffee brands.”'],
                    ['key' => 'milestone_tag', 'type' => 'text', 'label' => 'Milestone label', 'default' => 'Milestone Achieved'],
                ],
            ],
            [
                'label' => 'Featured In',
                'fields' => [
                    ['key' => 'featured_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Featured In'],
                    ['key' => 'featured_video_1', 'type' => 'url', 'label' => 'YouTube embed URL 1', 'default' => 'https://www.youtube.com/embed/jdrK-nd7SC0?rel=0&modestbranding=1'],
                    ['key' => 'featured_video_2', 'type' => 'url', 'label' => 'YouTube embed URL 2', 'default' => 'https://www.youtube.com/embed/FrjE6SI2laY?rel=0&modestbranding=1'],
                ],
            ],
        ],
        'repeaters' => [
            'brands' => [
                'label' => 'Brand logos',
                'add' => 'Add brand',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Name'],
                    ['key' => 'url', 'type' => 'url', 'label' => 'URL (optional)'],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Logo'],
                ],
                'default' => [
                    ['label' => 'Chai Macha', 'url' => 'https://chaimacha.com/', 'image' => 'images/brands/chaimacha.jpg'],
                    ['label' => 'Andaal Home Foods', 'url' => 'https://www.andaalhomefoods.com/', 'image' => 'images/brands/andaal.jpg'],
                    ['label' => 'Macha Dhaba', 'url' => '', 'image' => 'images/brands/macha-dhaba.webp'],
                    ['label' => 'Oasis Kitchen', 'url' => '', 'image' => 'images/brands/oasis.png'],
                    ['label' => 'Ullikaram Pesarattu', 'url' => '', 'image' => 'images/brands/ullikaram.webp'],
                    ['label' => 'Shakers & Movers', 'url' => 'https://www.shakersandmovers.in/', 'image' => 'images/brands/shakers.jpg'],
                ],
            ],
            'awards' => [
                'label' => 'Awards / milestones',
                'add' => 'Add award',
                'fields' => [
                    ['key' => 'year', 'type' => 'text', 'label' => 'Year'],
                    ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['key' => 'from', 'type' => 'text', 'label' => 'From'],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Photo'],
                ],
                'default' => [
                    ['year' => '2021', 'title' => 'Business Award for Best Coffee Shops', 'from' => 'From TIMES OF INDIA - 2021', 'image' => '2024/11/NVD_0369-scaled.jpg'],
                    ['year' => '2022', 'title' => 'Young Entrepreneur Award', 'from' => 'From SUN NETWORK - 2022', 'image' => '2024/11/Untitled-design-24.jpg'],
                    ['year' => '2024', 'title' => 'Viswaguru National Award', 'from' => 'From KAMADHENU - 2024', 'image' => '2025/07/Untitled-design-10.webp'],
                    ['year' => '2024', 'title' => 'Business Innovation Award', 'from' => 'From JCI India - 2024', 'image' => '2024/11/Untitled-design-25.jpg'],
                ],
            ],
        ],
    ],

    'menu' => [
        'label' => 'Menu',
        'path' => 'menu/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Menu | Filter Coffee, Beverages & South Indian Snacks'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
        ],
        'repeaters' => [
            'items' => [
                'label' => 'Menu tiles (column 1–3, top to bottom)',
                'add' => 'Add tile',
                'fields' => [
                    ['key' => 'column', 'type' => 'select', 'label' => 'Column', 'options' => ['1' => 'Column 1', '2' => 'Column 2', '3' => 'Column 3']],
                    ['key' => 'type', 'type' => 'select', 'label' => 'Type', 'options' => ['image' => 'Image / GIF', 'video' => 'Video']],
                    ['key' => 'file', 'type' => 'file', 'label' => 'File'],
                    ['key' => 'label', 'type' => 'text', 'label' => 'Alt / label'],
                ],
                'default' => [
                    ['column' => '1', 'type' => 'video', 'file' => '2026/06/COFFEE.mp4', 'label' => 'Coffee'],
                    ['column' => '1', 'type' => 'image', 'file' => '2025/11/cold-milk.gif', 'label' => 'Cold milk'],
                    ['column' => '1', 'type' => 'image', 'file' => '2025/11/mojhito.gif', 'label' => 'Mojito'],
                    ['column' => '1', 'type' => 'image', 'file' => '2025/11/snacks-1.gif', 'label' => 'Snacks'],
                    ['column' => '1', 'type' => 'image', 'file' => '2025/11/fries-1.gif', 'label' => 'Fries'],
                    ['column' => '2', 'type' => 'video', 'file' => '2026/06/HOT_MILK.mp4', 'label' => 'Hot milk'],
                    ['column' => '2', 'type' => 'video', 'file' => '2026/06/COLD_COFFEE.mp4', 'label' => 'Cold coffee'],
                    ['column' => '2', 'type' => 'image', 'file' => '2025/11/lassi.gif', 'label' => 'Lassi'],
                    ['column' => '2', 'type' => 'image', 'file' => '2025/11/sandwitch-1.gif', 'label' => 'Sandwich'],
                    ['column' => '2', 'type' => 'image', 'file' => '2025/11/dessert-1.gif', 'label' => 'Dessert'],
                    ['column' => '3', 'type' => 'video', 'file' => '2026/06/TEA.mp4', 'label' => 'Tea'],
                    ['column' => '3', 'type' => 'video', 'file' => '2026/06/MILK_SHAKE.mp4', 'label' => 'Milkshake'],
                    ['column' => '3', 'type' => 'image', 'file' => '2025/11/corn-1.gif', 'label' => 'Corn'],
                    ['column' => '3', 'type' => 'image', 'file' => '2025/11/momo-1.gif', 'label' => 'Momo'],
                ],
            ],
        ],
    ],

    'shop' => [
        'label' => 'Shop',
        'path' => 'shop/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Shop – CHENNAPATNAM FILTER COFFEE'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'banner_image', 'type' => 'image', 'label' => 'Banner image', 'default' => '2026/04/banner1.jpeg'],
                    ['key' => 'banner_alt', 'type' => 'text', 'label' => 'Banner alt text', 'default' => 'All your favourite South Indian snacks, all in one place — Andaal Home Foods'],
                    ['key' => 'banner_url', 'type' => 'url', 'label' => 'Banner / button URL', 'default' => 'https://www.andaalhomefoods.com/'],
                    ['key' => 'copy', 'type' => 'textarea', 'label' => 'Body copy', 'default' => 'Explore the authentic taste of tradition with Andaal Home Foods. From delicious savouries and sweets to aromatic podis, pickles, Coffee & Tea, and pure organic honey — crafted with care to bring you homemade goodness in every bite'],
                    ['key' => 'button_label', 'type' => 'text', 'label' => 'Button label', 'default' => 'Shop now'],
                ],
            ],
        ],
    ],

    'franchise' => [
        'label' => 'Franchise',
        'path' => 'franchise/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Best Filter Coffee Franchise in India | Franchise Cost & Investment'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Own a profitable filter coffee franchise in India with Chennapatnam Filter Coffee Franchise - low investment, proven ROI, 150+ outlets.'],
                ],
            ],
            [
                'label' => 'Enquiry form',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Franchise Enquiry'],
                    ['key' => 'lead_seo', 'type' => 'textarea', 'label' => 'SEO lead (before thank-you)', 'default' => 'Build a profitable filter coffee business with Chennapatnam\'s authentic South Indian filter coffee franchise model.'],
                    ['key' => 'lead', 'type' => 'textarea', 'label' => 'Thank-you paragraph', 'default' => 'Thank you for your interest in becoming a part of our growing brand. We are excited to explore potential partnerships with passionate entrepreneurs who share our vision and commitment to excellence. By filling out the form below, you are taking the first step toward owning and operating a franchise with us. Please provide accurate details so our team can evaluate your inquiry and get in touch with you at the earliest. We look forward to building a successful partnership together.'],
                    ['key' => 'submit_label', 'type' => 'text', 'label' => 'Submit button', 'default' => 'Submit Form'],
                ],
            ],
            [
                'label' => 'Why choose CFC',
                'fields' => [
                    ['key' => 'why_heading', 'type' => 'text', 'label' => 'Heading', 'default' => 'Choosing CFC as High Growth Franchise Opportunity'],
                    ['key' => 'for_heading', 'type' => 'text', 'label' => 'Phone heading', 'default' => 'For Franchise'],
                    ['key' => 'pdf_label', 'type' => 'text', 'label' => 'Franchise PDF label', 'default' => 'Franchise PDF'],
                    ['key' => 'pdf_presentation_label', 'type' => 'text', 'label' => 'Outlet presentation label', 'default' => 'Franchise Outlet Presentation'],
                    ['key' => 'pdf_3d_label', 'type' => 'text', 'label' => '3D PDF label', 'default' => 'Franchise 3D View PDF'],
                ],
            ],
            [
                'label' => 'Photo & testimonials',
                'fields' => [
                    ['key' => 'photo', 'type' => 'image', 'label' => 'Wide outlet photo', 'default' => '2024/03/ggg-1.png'],
                    ['key' => 'quotes_heading', 'type' => 'text', 'label' => 'Testimonials heading', 'default' => 'What Our Coffee Franchise Owners Say'],
                ],
            ],
        ],
        'repeaters' => [
            'why' => [
                'label' => 'Why-choose cards',
                'add' => 'Add card',
                'fields' => [
                    ['key' => 'column', 'type' => 'select', 'label' => 'Column', 'options' => ['1' => 'Left', '2' => 'Right']],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'p1', 'type' => 'textarea', 'label' => 'Paragraph 1'],
                    ['key' => 'p2', 'type' => 'textarea', 'label' => 'Paragraph 2'],
                    ['key' => 'p3', 'type' => 'textarea', 'label' => 'Paragraph 3 (optional)'],
                ],
                'default' => [
                    [
                        'column' => '1',
                        'heading' => 'Tradition in every Sip of Coffee',
                        'p1' => 'In a World dominated by Coffee, Chennapatnam Filter Coffee aspires to stand out as a brand that reignites the authentic aroma of classic filter coffee for enthusiasts. You can recall the mornings when your grandmother expertly brewed the hot filter coffee. We guarantee that a single cup of coffee will transport you back to those cherished memories.',
                        'p2' => 'Our unique approach and diverse menu of over 50+ hot and cold beverages, including the timeless filter coffee, offer customers an unforgettable experience. Join us in redefining authenticity in the coffee industry.',
                        'p3' => 'Our authentic South Indian filter coffee menu creates strong repeat customers and long-term brand loyalty.',
                    ],
                    [
                        'column' => '1',
                        'heading' => 'Experienced Guidance',
                        'p1' => 'Benefit from the rich experience behind Chennapatnam Filter Coffee. Our team has successfully managed more than 150 outlets, and we are here to guide you every step of the way.',
                        'p2' => 'From setting up your business to day-to-day operations, our expertise ensures your success.',
                        'p3' => 'From setup and training to daily operations, our franchise support system helps partners succeed faster.',
                    ],
                    [
                        'column' => '2',
                        'heading' => 'Low Investment Coffee Franchise with High Profit Potential',
                        'p1' => 'At CFC, we believe that success should not come at a hefty price. Our meticulously planned outlets are designed to minimize operating costs, allowing you to enjoy high-profit margins without the burden of heavy investments.',
                        'p2' => 'Start small and Dream Big with Chennapatnam Filter Coffee.',
                        'p3' => '',
                    ],
                    [
                        'column' => '2',
                        'heading' => 'Scalable Coffee Franchise Models for Every Entrepreneur',
                        'p1' => 'Chennapatnam Filter Coffee outlet requires at least 50 sq ft of space to start with. For those who have grand plans, we can plan and execute up to 2- Acre establishments.',
                        'p2' => 'Our franchise model is scalable, allowing you to choose the size that suits your vision. Larger outlets have proven to yield profit margins reaching up to 80%.',
                        'p3' => '',
                    ],
                ],
            ],
            'quotes' => [
                'label' => 'Franchise owner testimonials',
                'add' => 'Add testimonial',
                'fields' => [
                    ['key' => 'image', 'type' => 'image', 'label' => 'Headshot'],
                    ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                    ['key' => 'role', 'type' => 'text', 'label' => 'Role / location'],
                    ['key' => 'text', 'type' => 'textarea', 'label' => 'Quote'],
                    ['key' => 'position', 'type' => 'text', 'label' => 'Photo crop (object-position)', 'default' => '50% 0%'],
                ],
                'default' => [
                    ['image' => '2024/12/RAMESH-KONDAPUR.jpeg', 'name' => 'Ramesh', 'role' => 'Franchise Owner - Kondapur', 'text' => 'Six months into this journey with this franchise, and I couldn\'t be happier with my decision. The support and guidance from the brand have been remarkable. Supply of raw materials on-time makes the business to run with ease. Low risk, high satisfaction!', 'position' => '50% 0%'],
                    ['image' => '2024/12/MOULI-PM-PALEM.jpeg', 'name' => 'Mouli', 'role' => 'Franchise Owner - PM Palem, Vizag', 'text' => 'I started with one franchise in 2023, and seeing the success, I’ve already opened another in 2024. The process is seamless, and the brand’s trustworthiness is unmatched. We’re now planning to expand further!', 'position' => '50% 0%'],
                    ['image' => '2024/12/RAM-HUZURNAGAR.jpeg', 'name' => 'Ram', 'role' => 'Franchise Owner - Huzurnagar', 'text' => 'The level of commitment this brand shows is outstanding. From helping with procedures to regular check-ins, they’ve made the entire process smooth. Investing here was the best decision for my funds.', 'position' => '50% 12%'],
                    ['image' => '2024/12/SHAMSHUDDIN-KODAD-CITY.jpeg', 'name' => 'Shamshuddin', 'role' => 'Franchise Owner - Kodad City', 'text' => 'After seeing my cousin’s success, I decided to take up a franchise in Tadepalli, and it’s been amazing. The trust and guidance offered by the brand have been exceptional. Looking forward to many more years of success.', 'position' => '50% 36%'],
                ],
            ],
        ],
    ],

    'blog' => [
        'label' => 'Blog listing',
        'path' => 'blog/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Filter Coffee Blog | Brewing Tips, Recipes & Stories'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Stories, franchise guides, and brewing notes from Chennapatnam Filter Coffee.'],
                ],
            ],
            [
                'label' => 'Listing labels',
                'fields' => [
                    ['key' => 'filter_all', 'type' => 'text', 'label' => 'All filter', 'default' => 'All'],
                    ['key' => 'filter_franchise', 'type' => 'text', 'label' => 'Franchise filter', 'default' => 'Franchise'],
                    ['key' => 'read_more', 'type' => 'text', 'label' => 'Read more', 'default' => 'Read Full Story...'],
                    ['key' => 'load_more', 'type' => 'text', 'label' => 'Load more', 'default' => 'Load More'],
                    ['key' => 'end', 'type' => 'text', 'label' => 'End of list', 'default' => 'End of Content.'],
                ],
            ],
        ],
    ],

    'gallery' => [
        'label' => 'Gallery SEO',
        'path' => 'gallery/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Gallery | Chennapatnam Filter Coffee Outlets & Moments'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
        ],
    ],

    'media' => [
        'label' => 'Media Hub SEO',
        'path' => 'media-hub/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Media Hub | Chennapatnam Coffee Press & Awards'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Join us in Our Insta family@chennapatnamfiltercoffee'],
                    ['key' => 'instagram', 'type' => 'url', 'label' => 'Instagram profile URL', 'default' => 'https://www.instagram.com/chennapatnamfiltercoffee/'],
                    ['key' => 'load_more', 'type' => 'text', 'label' => 'Load more label', 'default' => 'Load More'],
                    ['key' => 'follow', 'type' => 'text', 'label' => 'Follow button', 'default' => 'Follow on Instagram'],
                ],
            ],
        ],
    ],

    'contact' => [
        'label' => 'Contact',
        'path' => 'contact-us/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Contact Chennapatnam Filter Coffee | Reach Our Team'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'kicker', 'type' => 'text', 'label' => 'Kicker', 'default' => 'CONTACT US'],
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Get in touch with us!'],
                    ['key' => 'submit_label', 'type' => 'text', 'label' => 'Submit button', 'default' => 'Get in Touch with Us'],
                    ['key' => 'art_image', 'type' => 'image', 'label' => 'Andaal illustration', 'default' => '2024/04/gundmma-katha-copy.webp'],
                ],
            ],
        ],
    ],

    'landing' => [
        'label' => 'Landing',
        'path' => 'landing/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Brewed with Tradition. Served with Passion.'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Brewed with Tradition. Served with Passion.'],
                    ['key' => 'lead', 'type' => 'textarea', 'label' => 'Lead paragraph', 'default' => 'Chennapatnam Filter Coffee offers a golden opportunity for passionate entrepreneurs to join our family.'],
                    ['key' => 'submit_label', 'type' => 'text', 'label' => 'Submit button', 'default' => 'Submit'],
                ],
            ],
        ],
    ],

    'thankyou' => [
        'label' => 'Thank you',
        'path' => 'thank-you/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Thank You | Chennapatnam Filter Coffee'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Thank You'],
                    ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'default' => 'We have received your details and will get in touch shortly.'],
                    ['key' => 'home_label', 'type' => 'text', 'label' => 'Back to home label', 'default' => 'Back to Home'],
                ],
            ],
        ],
    ],

    'privacy' => [
        'label' => 'Privacy Policy',
        'path' => 'privacy-policy/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Privacy Policy | Chennapatnam Filter Coffee'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Privacy Policy'],
                    ['key' => 'body', 'type' => 'html', 'label' => 'Body (HTML or paragraphs)', 'default' => "<p>This Privacy Policy describes how Chennapatnam Filter Coffee collects, uses, and protects information you provide on this website.</p>\n<h2>1. Information we collect</h2>\n<p>Name, email, mobile number, city, and message when you submit a contact or franchise form.</p>\n<h2>2. How we use information</h2>\n<p>To respond to enquiries, share franchise information, and improve our services.</p>\n<h2>3. Sharing</h2>\n<p>We do not sell personal information. We may share it with service partners only as needed to fulfil your request.</p>\n<h2>4. Contact</h2>\n<p>chennapatnamfiltercoffee@gmail.com · +91 94573 09999</p>"],
                ],
            ],
        ],
    ],

    'terms' => [
        'label' => 'Terms and Conditions',
        'path' => 'terms-and-conditions/',
        'groups' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Terms and Conditions | Chennapatnam Filter Coffee'],
                    ['key' => 'seo_description', 'type' => 'textarea', 'label' => 'Meta description', 'default' => 'Authentic South Indian filter coffee franchise from the house of Andaal.'],
                ],
            ],
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'Terms and Conditions'],
                    ['key' => 'body', 'type' => 'html', 'label' => 'Body (HTML or paragraphs)', 'default' => "<p>By using this website you agree to these terms.</p>\n<h2>1. Use of the site</h2>\n<p>Content is provided for information about Chennapatnam Filter Coffee and franchise opportunities.</p>\n<h2>2. Franchise enquiries</h2>\n<p>Submitting a form does not create a franchise agreement. All partnerships are subject to separate written contracts.</p>\n<h2>3. Intellectual property</h2>\n<p>Logos, images, videos, and copy remain the property of Chennapatnam Filter Coffee / the House of Andaal.</p>\n<h2>4. Contact</h2>\n<p>chennapatnamfiltercoffee@gmail.com</p>"],
                ],
            ],
        ],
    ],

    'notfound' => [
        'label' => '404 page',
        'path' => '',
        'groups' => [
            [
                'label' => 'Content',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading', 'default' => 'The page can’t be found.'],
                    ['key' => 'home_label', 'type' => 'text', 'label' => 'Home button', 'default' => 'Home'],
                    ['key' => 'seo_title', 'type' => 'text', 'label' => 'Page title', 'default' => 'Page not found – CHENNAPATNAM FILTER COFFEE'],
                ],
            ],
        ],
    ],
];
