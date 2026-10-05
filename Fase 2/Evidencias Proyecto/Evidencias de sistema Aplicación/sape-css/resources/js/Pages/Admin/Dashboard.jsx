import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';

export default function PanelDirector({ auth }) {
    const [vistaActiva, setVistaActiva] = useState('estadisticas');

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Panel de Dirección" />

            <div className="max-w-screen-2xl mx-auto flex flex-col md:flex-row min-h-[calc(100vh-4rem)]">
                
                {/* BARRA LATERAL IZQUIERDA (Sidebar del Director) */}
                <aside className="w-full md:w-72 bg-white border-r border-gray-200 shadow-sm flex-shrink-0 relative z-10">
                    <div className="p-6">
                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Centro de Mando
                        </h2>
                        <nav className="space-y-2 mb-6">
                            <button
                                onClick={() => setVistaActiva('estadisticas')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'estadisticas' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span className="font-semibold text-sm">Estadísticas Globales</span>
                            </button>

                            <button
                                onClick={() => setVistaActiva('profesores')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'profesores' ? 'bg-[#B91C1C] text-white shadow-md' : 'text-gray-600 hover:bg-red-50 hover:text-[#B91C1C]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span className="font-semibold text-sm">Plantel Docente</span>
                            </button>
                        </nav>

                        <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Herramientas Avanzadas</h2>
                        <nav className="space-y-2">
                            <button
                                onClick={() => setVistaActiva('ia')}
                                className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 group ${
                                    vistaActiva === 'ia' ? 'bg-[#002855] text-white shadow-md' : 'text-gray-600 hover:bg-blue-50 hover:text-[#002855]'
                                }`}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span className="font-semibold text-sm">Gestión predictiva IA</span>
                            </button>
                        </nav>
                    </div>
                </aside>

                {/* ÁREA PRINCIPAL DERECHA */}
                <main className="flex-1 p-6 md:p-8 bg-gray-50 overflow-y-auto">
                    <AnimatePresence mode="wait">
                        
                        {vistaActiva === 'estadisticas' && (
                            <motion.div key="stats" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -20 }} className="space-y-6">
                                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-[#002855]">
                                    <h1 className="text-2xl font-bold text-[#002855]">Visión General del Colegio</h1>
                                    <p className="text-gray-500 text-sm mt-1">Monitoreo de rendimiento académico institucional.</p>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Tarjetas de Muestra */}
                                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                        <div className="p-3 bg-blue-50 text-[#002855] rounded-lg"><svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                                        <div><p className="text-sm text-gray-500 font-semibold">Total Matrícula</p><p className="text-2xl font-bold text-gray-800">Cargando...</p></div>
                                    </div>
                                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                        <div className="p-3 bg-red-50 text-[#B91C1C] rounded-lg"><svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div>
                                        <div><p className="text-sm text-gray-500 font-semibold">Promedio Institucional</p><p className="text-2xl font-bold text-gray-800">-</p></div>
                                    </div>
                                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                                        <div className="p-3 bg-yellow-50 text-yellow-600 rounded-lg"><svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
                                        <div><p className="text-sm text-gray-500 font-semibold">Alumnos en Riesgo</p><p className="text-2xl font-bold text-gray-800">-</p></div>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        {/* Vistas en construcción */}
                        {(vistaActiva === 'profesores' || vistaActiva === 'ia') && (
                            <motion.div key="construccion" initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} className="flex flex-col items-center justify-center min-h-[60vh]">
                                <div className="bg-white p-10 rounded-2xl shadow-sm border border-gray-200 text-center max-w-md w-full">
                                    <h2 className="text-2xl font-bold text-[#002855] mb-2">{vistaActiva === 'profesores' ? 'Gestión de Profesores' : 'Predicción Académica IA'}</h2>
                                    <p className="text-gray-500 mb-8">Esta sección administrativa está actualmente en fase de desarrollo.</p>
                                    <div className="inline-block px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold border border-yellow-200">🚧 EN CONSTRUCCIÓN 🚧</div>
                                </div>
                            </motion.div>
                        )}

                    </AnimatePresence>
                </main>
            </div>
        </AuthenticatedLayout>
    );
}