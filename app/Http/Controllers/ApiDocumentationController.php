<?php

namespace App\Http\Controllers;

use App\Services\Documentations\DocumentationService;
use Illuminate\Support\Str;

class ApiDocumentationController extends Controller
{
    public function index(DocumentationService $service)
    {
        return $service->homePage();
    }

    /**
     * Return reusable CRUD descriptions for documentation cases.
     */
    public function returnDescription($modelName): array
    {
        $modelName = Str::singular(ucfirst((string) $modelName));

        return [
            'create' => [
                'name' => 'Create '.$modelName,
                'description' => 'Creates a new '.$modelName.' using the provided data.',
                'short_description' => 'Create a new '.$modelName.'.',
            ],
            'update' => [
                'name' => 'Update '.$modelName,
                'description' => 'Updates the specified '.$modelName.' using the provided data.',
                'short_description' => 'Update an existing '.$modelName.'.',
            ],
            'delete' => [
                'name' => 'Delete '.$modelName,
                'description' => 'Deletes the specified '.$modelName.' by its unique identifier.',
                'short_description' => 'Delete a '.$modelName.'.',
            ],
            'show' => [
                'name' => 'Get '.$modelName.' Details',
                'description' => 'Retrieves the details of a specific '.$modelName.'.',
                'short_description' => 'Retrieve '.$modelName.' details.',
            ],
            'index' => [
                'name' => 'Get '.$modelName.' List',
                'description' => 'Retrieves a paginated list of '.$modelName.' records.',
                'short_description' => 'Retrieve a list of '.$modelName.'.',
            ],
            'active' => [
                'name' => 'Toggle '.$modelName.' Status',
                'description' => 'Activates or deactivates the specified '.$modelName.'.',
                'short_description' => 'Switch the '.$modelName.' status.',
            ],
            'restore' => [
                'name' => 'Restore '.$modelName,
                'description' => 'Restores a soft-deleted '.$modelName.' record.',
                'short_description' => 'Restore a deleted '.$modelName.'.',
            ],
            'force_delete' => [
                'name' => 'Permanently Delete '.$modelName,
                'description' => 'Permanently removes a '.$modelName.' record.',
                'short_description' => 'Force delete '.$modelName.'.',
            ],
            'trash' => [
                'name' => 'Get Deleted '.$modelName,
                'description' => 'Retrieves soft-deleted '.$modelName.' records.',
                'short_description' => 'List deleted '.$modelName.'.',
            ],
        ];
    }
}
