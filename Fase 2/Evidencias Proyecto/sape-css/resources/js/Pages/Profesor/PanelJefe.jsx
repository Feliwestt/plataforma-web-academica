import React from 'react';
import { Head } from '@inertiajs/react';

export default function PanelJefe({ curso }) {
    if (!curso) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="p-6 bg-white rounded-lg shadow-md text-center">
                    <h2 className="text-xl font-bold text-gray-700">No tienes asignada una Jefatura.</h2>
                </div>
            </div>
        );
    }

    // Extraer asignaturas dinámicamente usando el nombre que agregamos en el Controlador
    const asignaturasSet = new Set();
    curso.estudiantes.forEach(est => {
        if (est.calificaciones) {
            est.calificaciones.forEach(cal => {
                if (cal.asignatura_nombre) asignaturasSet.add(cal.asignatura_nombre);
            });
        }
    });
    const asignaturas = Array.from(asignaturasSet);

    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <Head title={`Jefatura ${curso.nivel}° ${curso.letra}`} />
            
            <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h1 className="text-2xl font-bold text-gray-800">
                    Panel de Jefatura: {curso.nivel}° Medio {curso.letra}
                </h1>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm text-left text-gray-600">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th className="px-6 py-4 sticky left-0 bg-gray-50 shadow-[1px_0_0_0_#e5e7eb]">Estudiante</th>
                                {asignaturas.map(asig => (
                                    <th key={asig} className="px-6 py-4 font-semibold whitespace-nowrap min-w-[120px]">{asig}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {curso.estudiantes.map((estudiante, index) => (
                                <tr key={estudiante.id} className={`border-b ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                    <td className="px-6 py-3 font-medium text-gray-900 whitespace-nowrap sticky left-0 bg-inherit shadow-[1px_0_0_0_#e5e7eb]">
                                        {estudiante.nombres} {estudiante.apellidos}
                                    </td>
                                    {asignaturas.map(asig => {
                                        // Buscar las notas usando la columna valor
                                        const notas = estudiante.calificaciones?.filter(c => c.asignatura_nombre === asig) || [];
                                        const displayNota = notas.map(n => n.valor || n.evaluacion || n.evaluación).join(' / ');
                                        
                                        return <td key={asig} className="px-6 py-3">{displayNota || '-'}</td>;
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}