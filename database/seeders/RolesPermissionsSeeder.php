<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_dashboard' => 'Accéder au tableau de bord et à ses indicateurs.',
            'manage_sectors' => 'Créer, modifier, publier et supprimer les secteurs d’activité.',
            'manage_expertises' => 'Créer, modifier et organiser les expertises et leurs services.',
            'manage_services' => 'Gérer le catalogue des services proposés.',
            'manage_projects' => 'Créer, publier et gérer les réalisations (galerie, fiche technique).',
            'manage_testimonials' => 'Publier et modérer les témoignages clients.',
            'manage_statistics' => 'Mettre à jour les chiffres clés affichés sur le site.',
            'manage_settings' => 'Modifier les textes, visuels et réglages généraux du site.',
            'manage_pages' => 'Créer et modifier les pages éditoriales (dont les mentions légales).',
            'manage_leads' => 'Consulter, assigner et traiter les prospects reçus via le site.',
            'manage_users' => 'Créer, inviter, suspendre et supprimer les comptes utilisateurs.',
            'manage_roles' => 'Créer les rôles et répartir les permissions.',
            'view_sectors' => 'Consulter les secteurs en lecture seule (accès commercial).',
            'view_projects' => 'Consulter les réalisations en lecture seule (accès commercial).',
        ];

        foreach ($permissions as $name => $description) {
            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $matrix = [
            'Super administrateur' => [
                'description' => 'Accès complet à l’administration : contenus, prospects, utilisateurs, rôles et paramètres. Réservé aux responsables techniques du site.',
                'permissions' => array_keys($permissions),
            ],
            'Administrateur' => [
                'description' => 'Gère l’ensemble des contenus, les prospects et les utilisateurs. Ne peut pas modifier les rôles.',
                'permissions' => ['view_dashboard', 'manage_sectors', 'manage_expertises', 'manage_services', 'manage_projects', 'manage_testimonials', 'manage_statistics', 'manage_settings', 'manage_pages', 'manage_leads', 'manage_users', 'view_sectors', 'view_projects'],
            ],
            'Éditeur' => [
                'description' => 'Crée et met à jour les contenus du site — secteurs, expertises, réalisations, témoignages et pages — sans accès aux prospects ni aux utilisateurs.',
                'permissions' => ['view_dashboard', 'manage_sectors', 'manage_expertises', 'manage_services', 'manage_projects', 'manage_testimonials', 'manage_statistics', 'manage_pages', 'view_sectors', 'view_projects'],
            ],
            'Commercial' => [
                'description' => 'Traite les demandes reçues via le site : suivi des prospects, assignation et notes. Consulte les secteurs et les réalisations.',
                'permissions' => ['view_dashboard', 'manage_leads', 'view_sectors', 'view_projects'],
            ],
        ];

        foreach ($matrix as $roleName => $config) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['description' => $config['description']],
            );
            $role->syncPermissions($config['permissions']);
        }
    }
}
