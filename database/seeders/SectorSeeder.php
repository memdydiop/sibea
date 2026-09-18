<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            [
                'name' => 'BTP',
                'slug' => 'btp',
                'short_description' => 'Bâtiments, infrastructures et travaux publics.',
                'description' => 'Pôle historique du Groupe SIBEA, le BTP et le Génie Civil couvre l’ensemble du cycle de construction : études techniques, terrassement, VRD, gros œuvre, ouvrages d’art et aménagement foncier. Nos équipes interviennent pour les acteurs publics comme privés, en zone urbaine comme en milieu rural, avec une exigence constante de conformité aux normes de sécurité et de qualité.'
                    ."\n\n"
                    .'De la viabilisation d’un site à la livraison d’infrastructures complexes, nous mobilisons ingénierie de pointe et moyens matériels adaptés pour tenir les délais, maîtriser les coûts et limiter l’impact environnemental. Chaque chantier est piloté par des conducteurs de travaux expérimentés et suivi selon un plan qualité rigoureux, gage d’ouvrages durables et fiables.',
                'hero_title' => 'Construire des ouvrages qui durent.',
                'hero_description' => 'Bâtiments, infrastructures et travaux publics réalisés avec exigence, dans le respect des délais et des normes.',
                'hero_cta_label' => 'Découvrir le BTP',
                'is_active' => true,
                'sort_order' => 1,
                'page_intro_title' => 'Une expertise globale au service du développement durable.',
                'page_intro_text' => 'Acteur majeur du secteur constructif, notre pôle BTP et Génie Civil accompagne les acteurs publics et privés dans la concrétisation de leurs projets d’infrastructure. Qu’il s’agisse de terrassement, de réseaux de distribution ou de construction de structures complexes, nos équipes déploient un savoir-faire technique éprouvé pour livrer des ouvrages conformes aux plus hauts standards de sécurité, de qualité et de respect environnemental.',
                'page_cards' => [
                    ['title' => 'Génie civil & gros œuvre', 'text' => 'Ingénierie des structures complexes. Conception et réalisation d’ouvrages d’art, de fondations spéciales et de bâtiments industriels ou résidentiels, conçus pour durer.'],
                    ['title' => 'VRD (voirie & réseaux divers)', 'text' => 'La colonne vertébrale de vos projets. Maîtrise complète de la collecte des eaux, du déploiement des réseaux secs (électricité, télécoms) et de la viabilisation des sols avant construction.'],
                    ['title' => 'Aménagement & lotissement', 'text' => 'Valorisation et structuration foncière. Transformation de parcelles brutes en espaces de vie et d’activités optimisés : découpage, terrassement et intégration paysagère.'],
                    ['title' => 'Travaux publics & infrastructures', 'text' => 'Connecter les territoires. Réalisation de voies de circulation, de plateformes logistiques et d’infrastructures routières adaptées aux fortes contraintes de trafic.'],
                ],
                'page_cta_title' => 'Un projet d’aménagement ou de construction en vue ?',
                'page_cta_text' => 'Nos ingénieurs et conducteurs de travaux étudient votre cahier des charges pour vous proposer des solutions techniques et financières optimisées.',
                'page_cta_label' => 'Solliciter une étude technique',
            ],
            [
                'name' => 'Immobilier',
                'slug' => 'immobilier',
                'short_description' => 'Développement et valorisation immobilière.',
                'description' => 'Le pôle Immobilier conçoit, développe et valorise des programmes résidentiels et professionnels à Abidjan et en région. De la sélection foncière à la remise des clés, en passant par la commercialisation et la gestion locative, nous maîtrisons toute la chaîne de valeur grâce à notre synergie avec les équipes de construction du groupe.'
                    ."\n\n"
                    .'Nous proposons des appartements, villas, bureaux, plateformes logistiques et locaux commerciaux pensés pour les usages actuels : emplacements stratégiques, architectures contemporaines, finitions soignées et sécurité des investissements. Nos conseillers accompagnent investisseurs particuliers et entreprises à chaque étape de leur projet patrimonial.',
                'hero_title' => 'Valoriser le foncier, livrer des lieux de vie.',
                'hero_description' => 'Développement et réalisation de programmes immobiliers créateurs de valeur, à Abidjan et en région.',
                'hero_cta_label' => 'Découvrir l’immobilier',
                'is_active' => true,
                'sort_order' => 2,
                'page_intro_title' => 'Une vision intégrée de l’immobilier moderne.',
                'page_intro_text' => 'Notre pôle Immobilier conçoit et valorise des espaces adaptés aux nouveaux modes de vie et de travail. Grâce à notre synergie avec nos équipes de construction, nous maîtrisons l’ensemble de la chaîne de valeur : de la sélection foncière rigoureuse à la remise des clés, jusqu’à la gestion locative. Nous nous engageons à offrir des architectures contemporaines, des finitions soignées et un accompagnement sur mesure pour sécuriser vos investissements.',
                'page_cards' => [
                    ['title' => 'Promotion résidentielle', 'text' => 'Des adresses d’exception. Conception et commercialisation de programmes immobiliers neufs (appartements et villas) alliant confort moderne, sécurité et haute qualité constructive.'],
                    ['title' => 'Immobilier d’entreprise', 'text' => 'Optimiser vos espaces de travail. Développement de plateformes logistiques, de bureaux ergonomiques et de locaux commerciaux adaptés aux exigences de performance des entreprises.'],
                    ['title' => 'Aménagement & transactions', 'text' => 'Valoriser le potentiel foncier. Expertise dans l’acquisition, la viabilisation et la revente de terrains stratégiques, ainsi que l’accompagnement dans vos projets d’achat et de vente.'],
                    ['title' => 'Gestion locative & patrimoniale', 'text' => 'Votre investissement en toute sérénité. Gestion administrative, technique et financière de vos biens immobiliers pour garantir une rentabilité optimale et la pérennité de votre patrimoine.'],
                ],
                'page_cta_title' => 'Prêt à concrétiser votre projet immobilier ?',
                'page_cta_text' => 'Que vous souhaitiez acquérir votre résidence principale, investir dans le locatif ou implanter votre entreprise, nos conseillers vous guident pas à pas.',
                'page_cta_label' => 'Échanger avec un conseiller',
            ],
            [
                'name' => 'Énergie',
                'slug' => 'energie',
                'short_description' => 'Solutions énergétiques fiables et durables.',
                'description' => 'Face aux enjeux de la transition et de la souveraineté énergétique, le pôle Énergie conçoit, installe et maintient des infrastructures de production, de distribution et de stockage. Centrales solaires photovoltaïques, systèmes hybrides avec batteries, lignes HT/BT, postes de transformation : nous couvrons toute la chaîne de valeur, des études d’impact à la maintenance.'
                    ."\n\n"
                    .'Nos ingénieurs et techniciens accompagnent industries, opérateurs télécoms et collectivités pour sécuriser leur approvisionnement, réduire leur facture énergétique et diminuer leur empreinte carbone. Chaque solution est dimensionnée sur mesure, puis supervisée en continu pour garantir performance et continuité de service.',
                'hero_title' => 'L’énergie au service du développement.',
                'hero_description' => 'Des solutions énergétiques fiables pour alimenter durablement entreprises et collectivités.',
                'hero_cta_label' => 'Découvrir l’énergie',
                'is_active' => true,
                'sort_order' => 3,
                'page_intro_title' => 'L’ingénierie énergétique au service de votre performance.',
                'page_intro_text' => 'Face aux défis de la transition et de la souveraineté énergétique, notre pôle Énergie déploie des infrastructures d’approvisionnement et de production performantes. Nous intervenons sur toute la chaîne de valeur : études d’impact, audits de consommation, installation et maintenance. En associant énergies renouvelables et solutions hybrides, nous permettons aux entreprises et aux collectivités de réduire leur empreinte carbone tout en optimisant la fiabilité de leur alimentation électrique.',
                'page_cards' => [
                    ['title' => 'Énergies renouvelables & solaire', 'text' => 'Engager votre transition. Étude, dimensionnement et déploiement de centrales solaires photovoltaïques (au sol ou en toiture) adaptées aux besoins des sites isolés ou industriels.'],
                    ['title' => 'Systèmes hybrides & stockage', 'text' => 'Garantir une continuité totale. Intégration de solutions mixtes combinant solaire, stockage par batteries intelligentes et groupes de secours pour sécuriser vos équipements critiques.'],
                    ['title' => 'Réseaux & distribution électrique', 'text' => 'Acheminer l’énergie en toute sécurité. Conception, pose et raccordement de lignes électriques (HT/BT), de postes de transformation et d’infrastructures de distribution urbaines ou rurales.'],
                    ['title' => 'Efficacité énergétique & audits', 'text' => 'Optimiser pour mieux consommer. Analyse fine de vos profils de consommation, détection des pertes énergétiques et mise en place de solutions de gestion intelligente du réseau.'],
                ],
                'page_cta_title' => 'Envie d’optimiser ou de sécuriser votre approvisionnement ?',
                'page_cta_text' => 'Nos ingénieurs et techniciens réalisent un audit de vos besoins pour vous proposer une architecture énergétique performante et sur mesure.',
                'page_cta_label' => 'Demander une étude énergétique',
            ],
            [
                'name' => 'Agro-industrie',
                'slug' => 'agro-industrie',
                'short_description' => 'Transformation industrielle des matières premières agricoles.',
                'description' => 'Le pôle Agro-industrie transforme et conditionne localement les matières premières agricoles au sein d’unités industrielles modernes : huilerie, rizerie, conserverie et lignes de conditionnement. De la réception des récoltes à l’expédition des produits finis, chaque étape est contrôlée selon des procédures strictes de traçabilité et de sécurité alimentaire.'
                    ."\n\n"
                    .'En travaillant main dans la main avec les coopératives et les bassins de production, nous sécurisons nos approvisionnements tout en garantissant des revenus équitables aux producteurs. Notre objectif : créer de la valeur ajoutée locale, réduire les pertes post-récolte et approvisionner le marché en produits de qualité aux normes internationales.',
                'hero_title' => 'Transformer local, créer de la valeur.',
                'hero_description' => 'Unités de transformation agro-industrielle — huilerie, rizerie, conserverie, conditionnement — de la matière première au produit fini.',
                'hero_cta_label' => 'Découvrir l’agro-industrie',
                'is_active' => true,
                'sort_order' => 4,
                'page_intro_title' => 'Une chaîne de valeur intégrée, du champ au produit fini.',
                'page_intro_text' => 'Notre pôle Agro-industrie se positionne comme un pont essentiel entre le monde agricole et le marché de grande consommation. Grâce à nos unités industrielles modernes et connectées, nous assurons une transformation locale respectueuse des matières premières. Du contrôle rigoureux à la réception des récoltes jusqu’au conditionnement final, nous garantissons une traçabilité totale et le strict respect des normes internationales de sécurité alimentaire, contribuant activement au dynamisme économique de notre région.',
                'page_cards' => [
                    ['title' => 'Transformation industrielle', 'text' => 'Modernisation des processus. Exploitation d’unités de production de dernière génération pour transformer les récoltes brutes en produits semi-finis ou finis de qualité supérieure.'],
                    ['title' => 'Traçabilité & contrôle qualité', 'text' => 'L’exigence de la sécurité sanitaire. Suivi rigoureux de chaque lot grâce à des laboratoires intégrés, garantissant le respect des normes de sécurité alimentaire (HACCP).'],
                    ['title' => 'Logistique & chaîne d’approvisionnement', 'text' => 'Maîtriser les flux. Organisation d’une chaîne logistique fluide : collecte directe auprès des bassins agricoles, stockage sécurisé et distribution optimisée.'],
                    ['title' => 'Partenariats agricoles & durabilité', 'text' => 'Un ancrage local fort. Collaboration étroite avec des coopératives locales pour sécuriser nos volumes d’achats, tout en garantissant des revenus équitables et des pratiques agricoles responsables.'],
                ],
                'page_cta_title' => 'Vous recherchez un partenaire agro-industriel fiable ?',
                'page_cta_text' => 'Que ce soit pour un approvisionnement à grande échelle, du co-manufacturing ou une collaboration technique, nos équipes industrielles répondent à vos exigences.',
                'page_cta_label' => 'Prendre contact avec le pôle industriel',
            ],
        ];

        foreach ($sectors as $index => $data) {
            Sector::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_locked' => true]
            );
        }
    }
}
