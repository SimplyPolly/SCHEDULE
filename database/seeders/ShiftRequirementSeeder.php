<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftRequirement;

class ShiftRequirementSeeder extends Seeder
{
    public function run()
    {
        ShiftRequirement::truncate();

        // БУДНИЕ ДНИ 

        // ПОВАРЫ 6 сотрудников 
        $this->insertRule('weekday', 'day', 'cook', 3);     
        $this->insertRule('weekday', 'night', 'cook', 2);   

        // БАРМЕНЫ 3 сотрудника  
        $this->insertRule('weekday', 'day', 'bartender', 2); 
        $this->insertRule('weekday', 'night', 'bartender', 1); 

        // АДМИНИСТРАТОРЫ 2 сотрудника
        $this->insertRule('weekday', 'day', 'admin', 1);    
        $this->insertRule('weekday', 'night', 'admin', 1);  

        // ОФИЦИАНТЫ 4 сотрудника
        $this->insertRule('weekday', 'morning', 'waiter', 2);  
        $this->insertRule('weekday', 'day', 'waiter', 2);      
        $this->insertRule('weekday', 'night', 'waiter', 1);    

        // ХОСТЕС 4 сотрудника
        $this->insertRule('weekday', 'morning', 'hostess', 1); 
        $this->insertRule('weekday', 'day', 'hostess', 2);     
        $this->insertRule('weekday', 'night', 'hostess', 1);   

        // ВЫХОДНЫЕ ДНИ 

        // ПОВАРЫ
        $this->insertRule('weekend', 'day', 'cook', 3);    
        $this->insertRule('weekend', 'night', 'cook', 2); 

        // БАРМЕНЫ
        $this->insertRule('weekend', 'day', 'bartender', 2);
        $this->insertRule('weekend', 'night', 'bartender', 1); 

        // АДМИНИСТРАТОРЫ
        $this->insertRule('weekend', 'day', 'admin', 1);
        $this->insertRule('weekend', 'night', 'admin', 1);

        // ОФИЦИАНТЫ 
        $this->insertRule('weekend', 'morning', 'waiter', 2);
        $this->insertRule('weekend', 'day', 'waiter', 2);    
        $this->insertRule('weekend', 'night', 'waiter', 1);  

        // ХОСТЕС 
        $this->insertRule('weekend', 'morning', 'hostess', 2);
        $this->insertRule('weekend', 'day', 'hostess', 2);   
        $this->insertRule('weekend', 'night', 'hostess', 1); 

        $this->command->info('Требования к сменам обновлены!');
        $this->command->info('   - Уменьшены требования для ночных смен');
        $this->command->info('   - Скорректированы требования под реальное количество сотрудников');
    }

    private function insertRule($dayType, $shiftType, $role, $min)
    {
        ShiftRequirement::create([
            'day_type' => $dayType,
            'shift_type' => $shiftType,
            'role' => $role,
            'min_staff' => $min,
        ]);
    }
}
