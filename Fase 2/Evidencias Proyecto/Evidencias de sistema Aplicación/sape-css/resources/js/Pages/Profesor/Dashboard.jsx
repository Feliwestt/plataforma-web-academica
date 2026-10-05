import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ auth }) {
    // Obtener la fecha actual en español
    const fechaActual = new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Capitalizar la primera letra de la fecha
    const fechaFormateada = fechaActual.charAt(0).toUpperCase() + fechaActual.slice(1);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-[#002855] leading-tight">Portal Docente - Colegio San Sebastián</h2>}
        >
            <Head title="Dashboard Docente" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Tarjeta de Bienvenida */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                        <div className="p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h3 className="text-3xl font-bold text-[#002855] mb-2">
                                    Bienvenido, {auth.user.name}
                                </h3>
                                <p className="text-gray-500 text-lg capitalize">{fechaFormateada}</p>
                            </div>
                            <div className="bg-blue-50 text-[#002855] px-6 py-2 rounded-lg font-semibold border border-blue-100">
                                Año Escolar {new Date().getFullYear()}
                            </div>
                        </div>
                    </div>

                    {/* Tarjetas de Acceso Rápidos (Toda la tarjeta es clickeable) */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {/* Tarjeta Jefatura */}
                        <Link 
                            href={route('profesor.jefe')} 
                            className="group block bg-white rounded-xl shadow-sm border border-gray-200 border-t-4 border-t-[#002855] p-8 flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                        >
                            <div>
                                <div className="flex justify-between items-start mb-4">
                                    <h4 className="text-2xl font-bold text-gray-800 group-hover:text-[#002855] transition-colors">Mi Jefatura</h4>
                                    <div className="p-3 bg-blue-50 text-[#002855] rounded-full group-hover:bg-[#002855] group-hover:text-white transition-colors">
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                </div>
                                <p className="text-gray-500 mb-8">
                                    Revisa el historial completo de calificaciones de tu curso y el rendimiento general por subsector.
                                </p>
                            </div>
                            <div className="text-[#B91C1C] font-bold flex items-center gap-2 group-hover:text-red-800 transition-colors">
                                Acceder al panel <span className="transform group-hover:translate-x-1 transition-transform" aria-hidden="true">&rarr;</span>
                            </div>
                        </Link>

                        {/* Tarjeta Asignaturas */}
                        <Link 
                            href={route('profesor.asignatura')} 
                            className="group block bg-white rounded-xl shadow-sm border border-gray-200 border-t-4 border-t-[#B91C1C] p-8 flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                        >
                            <div>
                                <div className="flex justify-between items-start mb-4">
                                    <h4 className="text-2xl font-bold text-gray-800 group-hover:text-[#B91C1C] transition-colors">Mis Asignaturas</h4>
                                    <div className="p-3 bg-red-50 text-[#B91C1C] rounded-full group-hover:bg-[#B91C1C] group-hover:text-white transition-colors">
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477-4.5 1.253"></path></svg>
                                    </div>
                                </div>
                                <p className="text-gray-500 mb-8">
                                    Visualiza las calificaciones y gestiona el rendimiento de los alumnos en tus clases específicas.
                                </p>
                            </div>
                            <div className="text-[#002855] font-bold flex items-center gap-2 group-hover:text-blue-800 transition-colors">
                                Acceder al panel <span className="transform group-hover:translate-x-1 transition-transform" aria-hidden="true">&rarr;</span>
                            </div>
                        </Link>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}