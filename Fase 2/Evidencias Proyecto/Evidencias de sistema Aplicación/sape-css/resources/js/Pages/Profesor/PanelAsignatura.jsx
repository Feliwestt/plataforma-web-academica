import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function PanelAsignatura({ auth, cursos }) {
    // Estados principales
    const [vistaActiva, setVistaActiva] = useState('grafico');
    const [estudianteActivo, setEstudianteActivo] = useState(null);

    // Obtener el curso activo si se seleccionó uno específico
    const cursoActivo = useMemo(() => {
        return cursos?.find(c => c.id === vistaActiva) || null;
    }, [cursos, vistaActiva]);

    // Calcular promedios GENERALES de cada curso para el gráfico comparativo
    const datosComparativos = useMemo(() => {
        if (!cursos) return [];
        return cursos.map(curso => {
            let totalNotas = 0;
            let cantidadNotas = 0;
            curso.estudiantes.forEach(est => {
                curso.asignaturas.forEach(asig => {
                    const notas = est.calificaciones?.filter(c => c.asignatura_id === asig.id) || [];
                    notas.forEach(n => {
                        const valor = parseFloat(n.valor || n.evaluacion || n.evaluación);
                        if (!isNaN(valor)) {
                            totalNotas += valor;
                            cantidadNotas++;
                        }
                    });
                });
            });
            const promedio = cantidadNotas > 0 ? (totalNotas / cantidadNotas).toFixed(1) : 0;
            return {
                nombre: `${curso.nivel}° Medio ${curso.letra}`,
                promedio: parseFloat(promedio)
            };
        });
    }, [cursos]);

    // Función auxiliar para calcular promedio
    const calcularPromedio = (notas) => {
        const valores = notas.map(n => parseFloat(n.valor || n.evaluacion || n.evaluación)).filter(v => !isNaN(v));
        if (valores.length === 0) return '-';
        const suma = valores.reduce((a, b) => a + b, 0);
        return (suma / valores.length).toFixed(1);
    };

    if (!cursos || cursos.length === 0) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Mis Asignaturas" />
                <div className="min-h-[80vh] flex items-center justify-center">
                    <div className="p-8 bg-white rounded-xl shadow-sm text-center border border-gray-200">
                        <h2 className="text-xl font-bold text-gray-700">No tienes asignaturas asignadas este año.</h2>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Mis Asignaturas" />

            <div className="max-w-screen-2xl mx-auto flex flex-col md:flex-row min-h-[calc(100vh-4rem)]">
                
                {/* BARRA LATERAL IZQUIERDA (Sidebar) */}
                <aside className="w-full md:w-72 bg-white border-r border-gray-200 shadow-sm flex-shrink-0 relative z-10">
                    <div className="p-6">
                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Panel de Control
                        </h2>
                        <nav className="space-y-2 mb-6">
                            <button
                                onClick={() => { setVistaActiva('grafico'); setEstudianteActivo(null); }}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center justify-between group ${
                                    vistaActiva === 'grafico' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <svg className={`w-5 h-5 ${vistaActiva === 'grafico' ? 'text-white' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                                    <span className="font-semibold text-sm">Gráfico Comparativo</span>
                                </div>
                            </button>

                            <button
                                onClick={() => { setVistaActiva('chatbot'); setEstudianteActivo(null); }}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center justify-between group ${
                                    vistaActiva === 'chatbot' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <svg className={`w-5 h-5 ${vistaActiva === 'chatbot' ? 'text-white' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    <span className="font-semibold text-sm">Asistente Inteligente</span>
                                </div>
                            </button>
                        </nav>

                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Tus Cursos Asignados
                        </h2>
                        <nav className="space-y-2">
                            {cursos.map(curso => (
                                <button
                                    key={curso.id}
                                    // Al cambiar de curso, ocultamos el estudiante activo anterior
                                    onClick={() => { setVistaActiva(curso.id); setEstudianteActivo(null); }}
                                    className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center justify-between group ${
                                        vistaActiva === curso.id ? 'bg-[#B91C1C] text-white shadow-md' : 'text-gray-600 hover:bg-red-50 hover:text-[#B91C1C]'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <div className={`w-2 h-2 rounded-full ${vistaActiva === curso.id ? 'bg-white' : 'bg-gray-300 group-hover:bg-[#B91C1C]'}`}></div>
                                        <span className="font-semibold text-sm">{curso.nivel}° Medio {curso.letra}</span>
                                    </div>
                                </button>
                            ))}
                        </nav>
                    </div>
                </aside>

                {/* ÁREA PRINCIPAL DERECHA */}
                <main className="flex-1 p-6 md:p-8 bg-gray-50 overflow-y-auto">
                    <AnimatePresence mode="wait">
                        
                        {/* VISTA 1: GRÁFICO COMPARATIVO */}
                        {vistaActiva === 'grafico' && (
                            <motion.div key="vista-grafico" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} transition={{ duration: 0.3 }} className="space-y-6">
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-[#002855]">
                                    <h1 className="text-2xl font-bold text-[#002855]">Rendimiento General</h1>
                                    <p className="text-gray-500 text-sm mt-1">Comparativa de promedios entre todos tus cursos asignados.</p>
                                </div>
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                                    <h3 className="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2"><svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>Evolución de Promedios por Curso</h3>
                                    <div className="h-80 w-full">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={datosComparativos} margin={{ top: 10, right: 30, left: -20, bottom: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f3f4f6" />
                                                <XAxis dataKey="nombre" axisLine={false} tickLine={false} tick={{fill: '#6b7280', fontSize: 13}} padding={{ left: 30, right: 30 }} />
                                                <YAxis domain={[1, 7]} axisLine={false} tickLine={false} tick={{fill: '#6b7280', fontSize: 12}} />
                                                <Tooltip cursor={{stroke: '#e5e7eb', strokeWidth: 2}} contentStyle={{borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'}} />
                                                <Line type="monotone" dataKey="promedio" stroke="#B91C1C" strokeWidth={4} dot={{ r: 6, fill: '#B91C1C', stroke: '#fff', strokeWidth: 2 }} activeDot={{ r: 8, fill: '#002855', stroke: '#fff' }} name="Promedio del Curso" />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        {/* VISTA 2: CHATBOT */}
                        {vistaActiva === 'chatbot' && (
                            <motion.div key="vista-chatbot" initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} transition={{ duration: 0.3 }} className="flex flex-col items-center justify-center min-h-[60vh]">
                                <div className="bg-white p-10 rounded-2xl shadow-sm border border-gray-200 text-center max-w-md w-full">
                                    <div className="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6"><svg className="w-10 h-10 text-[#002855]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></div>
                                    <h2 className="text-2xl font-bold text-[#002855] mb-2">Asistente Inteligente</h2>
                                    <p className="text-gray-500 mb-8">Esta sección está actualmente en fase de desarrollo.</p>
                                    <div className="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold tracking-wide border border-yellow-200 shadow-sm">🚧 EN CONSTRUCCIÓN 🚧</div>
                                </div>
                            </motion.div>
                        )}

                        {/* VISTA 3.A: LISTA DE ESTUDIANTES DEL CURSO */}
                        {vistaActiva !== 'grafico' && vistaActiva !== 'chatbot' && cursoActivo && !estudianteActivo && (
                            <motion.div key="lista-estudiantes" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -20 }} transition={{ duration: 0.3 }} className="space-y-6">
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex justify-between items-center border-l-4 border-l-[#B91C1C]">
                                    <div>
                                        <h1 className="text-2xl font-bold text-[#002855]">{cursoActivo.nivel}° Medio {cursoActivo.letra}</h1>
                                        <p className="text-gray-500 text-sm mt-1">Selecciona un estudiante para revisar sus calificaciones.</p>
                                    </div>
                                    <div className="bg-red-50 text-[#B91C1C] px-4 py-2 rounded-lg text-sm font-semibold border border-red-100">{cursoActivo.estudiantes.length} Estudiantes</div>
                                </div>

                                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                    <table className="min-w-full text-sm text-left">
                                        <thead className="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th className="px-6 py-4 font-semibold tracking-wider">Estudiante</th>
                                                <th className="px-6 py-4 font-semibold tracking-wider text-center">RUT</th>
                                                <th className="px-6 py-4 font-semibold tracking-wider text-right">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {cursoActivo.estudiantes.map((estudiante) => (
                                                <tr key={estudiante.id} className="hover:bg-red-50/30 transition-colors">
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-10 h-10 rounded-full bg-[#002855]/10 flex items-center justify-center text-[#002855] font-bold text-sm">
                                                                {estudiante.nombres.charAt(0)}{estudiante.apellidos.charAt(0)}
                                                            </div>
                                                            <span className="font-semibold text-gray-900 text-base">{estudiante.nombres} {estudiante.apellidos}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-center text-gray-500 font-mono">{estudiante.run || 'Sin Registro'}</td>
                                                    <td className="px-6 py-4 text-right">
                                                        <button 
                                                            onClick={() => setEstudianteActivo(estudiante)}
                                                            className="inline-flex items-center gap-2 bg-white border border-[#B91C1C] text-[#B91C1C] px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#B91C1C] hover:text-white transition-colors"
                                                        >
                                                            Ver Calificaciones →
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </motion.div>
                        )}

                        {/* VISTA 3.B: DETALLE DEL ESTUDIANTE (Notas por semestre) */}
                        {vistaActiva !== 'grafico' && vistaActiva !== 'chatbot' && cursoActivo && estudianteActivo && (
                            <motion.div key="detalle-estudiante" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} transition={{ duration: 0.3 }} className="space-y-6">
                                
                                <button onClick={() => setEstudianteActivo(null)} className="text-gray-500 hover:text-[#B91C1C] font-semibold flex items-center gap-2 transition-colors">
                                    ← Volver a la nómina del curso
                                </button>

                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-t-4 border-t-[#002855]">
                                    <div className="flex items-center gap-4">
                                        <div className="w-16 h-16 rounded-full bg-[#002855]/10 flex items-center justify-center text-[#002855] font-bold text-2xl">
                                            {estudianteActivo.nombres.charAt(0)}{estudianteActivo.apellidos.charAt(0)}
                                        </div>
                                        <div>
                                            <h1 className="text-2xl font-bold text-gray-900">{estudianteActivo.nombres} {estudianteActivo.apellidos}</h1>
                                            <p className="text-gray-500 text-sm">Registro de Calificaciones - {cursoActivo.nivel}° {cursoActivo.letra}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full text-sm text-left">
                                            <thead className="text-xs text-gray-600 uppercase bg-gray-50 border-b border-gray-200">
                                                <tr>
                                                    <th className="px-6 py-4 font-bold bg-gray-100 w-1/3">Asignatura</th>
                                                    <th className="px-4 py-4 font-bold text-center border-l border-gray-200">1° Semestre</th>
                                                    <th className="px-4 py-4 font-bold text-center bg-gray-100">Prom. 1S</th>
                                                    <th className="px-4 py-4 font-bold text-center border-l border-gray-200">2° Semestre</th>
                                                    <th className="px-4 py-4 font-bold text-center bg-gray-100">Prom. 2S</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {cursoActivo.asignaturas.map(asig => {
                                                    // 1. Filtramos las notas de esta asignatura específica
                                                    const notas = estudianteActivo.calificaciones?.filter(c => c.asignatura_id === asig.id) || [];
                                                    
                                                    // 2. Intentamos separar por semestre si el dato existe
                                                    let notasS1 = notas.filter(c => Number(c.semestre) === 1);
                                                    let notasS2 = notas.filter(c => Number(c.semestre) === 2);

                                                    // 3. SALVAVIDAS: Si no hay dato de semestre, separamos las primeras 5 notas para S1 y el resto para S2
                                                    if (notasS1.length === 0 && notasS2.length === 0 && notas.length > 0) {
                                                        notasS1 = notas.slice(0, 5);
                                                        notasS2 = notas.slice(5);
                                                    }
                                                    
                                                    const promS1 = calcularPromedio(notasS1);
                                                    const promS2 = calcularPromedio(notasS2);

                                                    return (
                                                        <tr key={asig.id} className="hover:bg-blue-50/30">
                                                            <td className="px-6 py-4 font-semibold text-gray-800 bg-gray-50/50">
                                                                {asig.nombre}
                                                            </td>
                                                            
                                                            {/* Notas Semestre 1 */}
                                                            <td className="px-4 py-4 border-l border-gray-100">
                                                                <div className="flex flex-wrap gap-1 justify-center">
                                                                    {notasS1.length > 0 ? notasS1.map((n, i) => {
                                                                        const valor = n.valor || n.evaluacion || n.evaluación;
                                                                        return <span key={i} className={`inline-block px-2 py-1 rounded text-xs font-semibold border ${parseFloat(valor) < 4.0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-gray-700 border-gray-200'}`}>{valor}</span>;
                                                                    }) : <span className="text-gray-300">-</span>}
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-4 text-center font-bold text-gray-700 bg-gray-50">
                                                                <span className={parseFloat(promS1) < 4.0 ? 'text-red-600' : ''}>{promS1}</span>
                                                            </td>

                                                            {/* Notas Semestre 2 */}
                                                            <td className="px-4 py-4 border-l border-gray-100">
                                                                <div className="flex flex-wrap gap-1 justify-center">
                                                                    {notasS2.length > 0 ? notasS2.map((n, i) => {
                                                                        const valor = n.valor || n.evaluacion || n.evaluación;
                                                                        return <span key={i} className={`inline-block px-2 py-1 rounded text-xs font-semibold border ${parseFloat(valor) < 4.0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-gray-700 border-gray-200'}`}>{valor}</span>;
                                                                    }) : <span className="text-gray-300">-</span>}
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-4 text-center font-bold text-gray-700 bg-gray-50">
                                                                <span className={parseFloat(promS2) < 4.0 ? 'text-red-600' : ''}>{promS2}</span>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </main>
            </div>
        </AuthenticatedLayout>
    );
}