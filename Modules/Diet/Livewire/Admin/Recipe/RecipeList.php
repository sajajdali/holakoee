<?php

namespace Modules\Diet\Livewire\Admin\Recipe;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Modules\Recipe\app\Models\Recipe;

class RecipeList extends Component
{
    use WithPagination;

    public array $fetchData =[];
    public array $form =[];
    public $searchPanel;


    public function startSearch() {
        return $this->render();
    }
    public function ignoresearch() {
      unset(  $this->form['search']);
      return $this->render();
    }

    public function recipeImage(Recipe $recipe)
    {
        if (isset($recipe->detail['detail_image']) && file_exists(public_path('storage/' .$recipe->detail['detail_image']))) {
            $img = asset('storage/' . $recipe->detail['detail_image']);
            return ' <img src="'.$img.'"
                                                 alt="Recipe Image"
                                                 width="50"
                                                 height="50">';
        }

        if (isset($recipe->detail['detail_image']) && !file_exists(public_path('storage/' .$recipe->detail['detail_image']))) {
            $img = asset('assets/recipe/no_image.jpeg' );
            return '<img src="'.$img.'"
                                                 alt="Recipe Image"
                                                 width="50"
                                                 height="50"> <img src="'.$img.'"
                                                 alt="Recipe Image"
                                                 width="50"
                                                 height="50">';
        }

        $img = asset('assets/recipe/no_image.jpeg' );
        return ' <img src="'.$img.'"
                                                 alt="Recipe Image"
                                                 width="50"
                                                 height="50">';
    }

    #[On('delete')]
    public function deleteRecipe(Recipe $model) {
        $model->delete();
        return redirect()->route('admin.recipe.list')->with('success','دستور غذا با موفقیت حذف شذ');
    }

    public function copyRecipe($recipeId)
    {
        try {
            // Fetch the original recipe with related data
            $originalRecipe = Recipe::with(['units', 'basicFoods'])->findOrFail($recipeId);

            // Clone the recipe data
            $newRecipeData = $originalRecipe->replicate()->toArray();
            $newRecipeData['name'] = $originalRecipe->name . ' (Copy)';
            $newRecipeData['created_at'] = now();
            $newRecipeData['updated_at'] = now();

            // Create the new recipe
            $newRecipe = Recipe::create($newRecipeData);

            // Copy the unit relationship

            if ($originalRecipe->units) {
                foreach ($originalRecipe->units as $unit) {
                    $newRecipe->units()->create([
                        'food_unit_id' => $unit->food_unit_id,
                        'quantity_per_unit' => $unit->quantity_per_unit,
                    ]);
                }
            }

            // Copy the basic foods relationship
            $syncData = [];
            foreach ($originalRecipe->basicFoods as $basicFood) {
                $syncData[$basicFood->pivot->basic_food_id] = [
                    'food_unit_id' => $basicFood->pivot->food_unit_id,
                    'quantity_per_unit' => $basicFood->pivot->quantity_per_unit,
                ];
            }
            $newRecipe->basicFoods()->sync($syncData);




            return redirect()->route('admin.recipe.edit', $newRecipe->id)
                ->with('success', 'دستور با موفقیت ایجاد و شما در صفحه ویرایش آن هستید');
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'Error copying the recipe: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function duplicateRecipe($id)
    {
        return $this->copyRecipe($id);
    }
    public function render()
    {

        $query = Recipe::query()->orderByDesc('id');
        $searchCriteria = [
            'idSearch' => [
                'condition' => isset($this->form['search']['id']),
                'callback' => function ($query) {
                    return $query->whereId($this->form['search']['id']);
                },
            ],
            'nameSearch' => [
                'condition' => isset($this->form['search']['name']),
                'callback' => function ($query) {
                    return $query->where('name', 'LIKE', "%{$this->form['search']['name']}%");
                },
            ],
        ];
        foreach ($searchCriteria as $property => $config) {
            $condition = $config['condition'];
            $callback = $config['callback'];
            if ($condition) {
                $query->when($condition, $callback);
            }
        }

        return view('diet::livewire.admin.recipe.recipe-list',['recipes' => $query->paginate(50) ]);
    }
}
