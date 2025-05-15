<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function create()
    {
        return view('aluno.registro-ponto');
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Verificar se já existe um registro para hoje
        $today = now()->format('Y-m-d');
        $existingRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();
        
        if ($existingRecord) {
            // Se já tem entrada, registra saída
            if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                $existingRecord->exit_time = now();
                $existingRecord->save();
                
                return redirect()->route('dashboard')->with('success', 'Saída registrada com sucesso!');
            }
            
            return redirect()->route('dashboard')->with('error', 'Você já registrou entrada e saída hoje.');
        }
        
        // Criar novo registro
        $attendance = new AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->entry_time = now();
        $attendance->status = 'registered';
        $attendance->save();
        
        return redirect()->route('dashboard')->with('success', 'Entrada registrada com sucesso!');
    }
    
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $query = AttendanceRecord::where('user_id', $user->id);
        
        // Aplicar filtros
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $attendances = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('aluno.historico', compact('attendances'));
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array'
        ]);
        
        $inputDescriptor = $request->face_descriptor;
        
        // Obter todos os usuários com registros faciais
        $users = User::whereHas('recognitionRecords')->with('recognitionRecords')->get();
        
        $bestMatch = null;
        $bestMatchDistance = 0.6; // Limiar máximo para considerar uma correspondência (menor é melhor)
        
        foreach ($users as $user) {
            foreach ($user->recognitionRecords as $record) {
                if ($record->face_descriptor) {
                    $storedDescriptor = json_decode($record->face_descriptor);
                    
                    if ($storedDescriptor) {
                        $distance = $this->calculateEuclideanDistance($inputDescriptor, $storedDescriptor);
                        
                        if ($distance < $bestMatchDistance) {
                            $bestMatchDistance = $distance;
                            $bestMatch = $user;
                        }
                    }
                }
            }
        }
        
        if ($bestMatch) {
            // Verificar se já existe um registro para hoje
            $today = now()->format('Y-m-d');
            $existingRecord = AttendanceRecord::where('user_id', $bestMatch->id)
                ->whereDate('created_at', $today)
                ->first();
            
            $type = 'Entrada';
            
            if ($existingRecord) {
                // Se já tem entrada, registra saída
                if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                    $existingRecord->exit_time = now();
                    $existingRecord->save();
                    $type = 'Saída';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você já registrou entrada e saída hoje.'
                    ]);
                }
            } else {
                // Criar novo registro
                $attendance = new AttendanceRecord();
                $attendance->user_id = $bestMatch->id;
                $attendance->entry_time = now();
                $attendance->status = 'registered';
                $attendance->save();
            }
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $bestMatch->id,
                    'name' => $bestMatch->name,
                ],
                'type' => $type
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Usuário não reconhecido'
        ]);
    }
    
    private function calculateEuclideanDistance($vec1, $vec2)
    {
        if (count($vec1) !== count($vec2)) {
            return 999; // Retorna um valor grande para indicar incompatibilidade
        }
        
        $sum = 0;
        for ($i = 0; $i < count($vec1); $i++) {
            $sum += pow($vec1[$i] - $vec2[$i], 2);
        }
        
        return sqrt($sum);
    }
}