import React from 'react';
import { Head } from '@inertiajs/react';

export default function PanelAsignatura({ cursos }) {
    if (!cursos || cursos.length === 0) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="p-6 bg-white rounded-lg shadow-md text-center">
                    <h2 className="text-xl font-bold text-gray-700">No tienes asignaturas asignadas.</h2>
                </div>
            </div>
        );
    }

    return (
        <div className="p-6 max-w-6xl mx-auto space-y-8">
            <Head title="Panel de Asignatura" />
            
            <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h1 className="text-2xl font-bold text-gray-800">Panel de Calificaciones</h1>
            </div>

            {cursos.map(curso => (
                <div key={curso.id} className="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div className="px-6 py-4 border-b bg-blue-50">
                        <h2 className="text-lg font-bold text-blue-800">
                            Curso: {curso.nivel}° Medio {curso.letra}
                        </h2>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-700 uppercase bg-white border-b">
                                <tr>
                                    <th className="px-6 py-3">Estudiante</th>
                                    {curso.asignaturas.map(asig => (
                                        <th key={asig.id} className="px-6 py-3 text-center">Notas {asig.nombre}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {curso.estudiantes.map((estudiante, index) => (
                                    <tr key={estudiante.id} className={`border-b ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                        <td className="px-6 py-3 font-medium text-gray-900">
                                            {estudiante.nombres} {estudiante.apellidos}
                                        </td>
                                        {curso.asignaturas.map(asig => {
                                            const notas = estudiante.calificaciones?.filter(c => c.asignatura_id === asig.id) || [];
                                            const displayNota = notas.map(n => n.valor || n.evaluacion).join(' / ');
                                            
                                            return <td key={asig.id} className="px-6 py-3 text-center font-semibold">{displayNota || '-'}</td>;
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ))}
        </div>
    );
}