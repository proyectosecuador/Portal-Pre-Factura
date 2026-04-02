#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Procesador optimizado para Despacho
MODIFICADO: Extrae sede desde hoja "Data" columna GS (WHSEID) desde la fila 4 en adelante
"""

import pandas as pd
import logging
import os
from datetime import datetime

logger = logging.getLogger(__name__)

class DespachoProcesador:
    """Procesador para archivos de despacho"""
    
    COLUMNAS = {
        'ORDERKEY': {'col': 'C', 'tipo': 'string'},
        'SKU': {'col': 'D', 'tipo': 'string'},
        'STORERKEY': {'col': 'E', 'tipo': 'string'},
        'EXTERNORDERKEY': {'col': 'F', 'tipo': 'string'},
        'SHIPPEDQTY': {'col': 'O', 'tipo': 'float'},
        'UOM': {'col': 'I', 'tipo': 'string'},
        'STATUS': {'col': 'P', 'tipo': 'string'},
        'ADDDATE': {'col': 'CN', 'tipo': 'date'},
    }
    
    HEADERS = ['ORDERKEY', 'SKU', 'STORERKEY', 'EXTERNORDERKEY', 'UNIDADES', 
               'CAJAS', 'PALLETS', 'STATUS', 'ADDDATE']
    
    STATUS_VALIDOS = ['55', '92', '95']
    
    # Mapeo de WHSEID a SEDE
    MAPEO_SEDES = {
        'wmwhse6': 'Toborochi',
        'wmwhse7': 'Illimani',
        'wmwhse43': 'Tunari',
        'wmwhse44': 'Illampu',
        'wmwhse8': 'Quito',
        'wmwhse3': 'Guayaquil',
    }
    
    def __init__(self):
        self.logger = logger
    
    def _excel_col_to_index(self, col_letter):
        """Convierte letra de columna a índice"""
        result = 0
        for char in col_letter:
            result = result * 26 + (ord(char.upper()) - ord('A') + 1)
        return result - 1
    
    def _extraer_parte_entera(self, valor):
        """Extrae la parte entera de un número"""
        try:
            if pd.isna(valor):
                return 0
            if isinstance(valor, str):
                import re
                match = re.search(r'(\d+)', valor)
                return int(match.group(1)) if match else 0
            return int(float(valor))
        except:
            return 0
    
    def _convertir_fecha(self, valor):
        """Convierte fecha a formato YYYY-MM-DD"""
        if pd.isna(valor) or valor == '' or valor is None:
            return None
        
        try:
            if isinstance(valor, (datetime, pd.Timestamp)):
                return valor.strftime('%Y-%m-%d')
            
            if isinstance(valor, str):
                valor_str = str(valor).strip()
                
                formatos = [
                    '%d/%m/%y %H:%M',
                    '%d/%m/%Y %H:%M',
                    '%d/%m/%y',
                    '%d/%m/%Y',
                    '%Y-%m-%d',
                    '%Y/%m/%d',
                    '%d-%m-%Y',
                ]
                
                for fmt in formatos:
                    try:
                        fecha = datetime.strptime(valor_str, fmt)
                        return fecha.strftime('%Y-%m-%d')
                    except ValueError:
                        continue
                
                try:
                    fecha = pd.to_datetime(valor_str, dayfirst=True)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
                
                return valor_str
            
            if isinstance(valor, (int, float)):
                try:
                    fecha = pd.Timestamp.fromordinal(int(valor) - 693594)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
            
            return str(valor)
            
        except Exception as e:
            self.logger.error(f"Error convirtiendo fecha {valor}: {e}")
            return None
    
    def _extraer_sede_desde_hoja_data(self, archivo_path):
        """
        Extrae la sede desde la hoja "Data" columna GS (WHSEID)
        Busca en las filas desde la fila 4 en adelante (índice 3)
        Retorna: nombre de la sede o None si no se encuentra
        """
        print(f"\n{'='*60}")
        print("🔍 BUSCANDO SEDE DESDE HOJA 'Data'")
        print(f"{'='*60}")
        
        try:
            # Leer el archivo Excel completo
            excel_file = pd.ExcelFile(archivo_path, engine='openpyxl')
            sheet_names = excel_file.sheet_names
            
            print(f"📊 Hojas disponibles: {sheet_names}")
            
            # Buscar hoja que se llame exactamente "Data" (case insensitive)
            hoja_data = None
            for name in sheet_names:
                if name.lower() == 'data':
                    hoja_data = name
                    break
            
            if not hoja_data:
                print("⚠️ No se encontró hoja 'Data' en el archivo")
                return None
            
            print(f"📄 Hoja 'Data' encontrada: {hoja_data}")
            
            # Leer la hoja Data SIN encabezado para tener control exacto de filas
            df_data = pd.read_excel(archivo_path, sheet_name=hoja_data, engine='openpyxl', header=None)
            print(f"📊 Hoja Data tiene {len(df_data)} filas y {len(df_data.columns)} columnas")
            
            # Calcular índice de columna GS
            col_gs_index = self._excel_col_to_index('GS')
            print(f"📍 Columna GS (letra) = índice {col_gs_index}")
            
            # Verificar que la columna existe
            if col_gs_index >= len(df_data.columns):
                print(f"⚠️ La columna GS (índice {col_gs_index}) no existe. Máxima columna: {len(df_data.columns)-1}")
                return None
            
            # MOSTRAR ESTRUCTURA DE LAS PRIMERAS 10 FILAS PARA DEPURACIÓN
            print("\n🔍 ESTRUCTURA DE LA HOJA DATA (primeras 10 filas, columna GS):")
            print("=" * 60)
            for i in range(min(10, len(df_data))):
                valor = df_data.iloc[i, col_gs_index] if col_gs_index < len(df_data.columns) else 'N/A'
                print(f"Fila {i}: {valor}")
            print("=" * 60)
            
            # Buscar la primera fila que contenga un WHSEID válido (wmwhse6, wmwhse7, etc.)
            # Empezamos desde la fila 3 (índice 2) para saltar posibles encabezados
            # Pero para estar más seguros, empezamos desde la fila 4 (índice 3)
            fila_inicio = 3  # Fila 4 en Excel (índice 3)
            print(f"📌 Buscando valores desde fila {fila_inicio} en adelante (índice {fila_inicio})")
            
            # Lista de WHSEID válidos para identificar
            whseid_validos = list(self.MAPEO_SEDES.keys())
            print(f"📌 WHSEID válidos: {whseid_validos}")
            
            # Extraer valores de la columna GS desde la fila_inicio en adelante
            valores_whseid = []
            for i in range(fila_inicio, len(df_data)):
                valor = df_data.iloc[i, col_gs_index]
                if pd.notna(valor):
                    valor_str = str(valor).strip().lower()
                    # Verificar que no sea un encabezado como 'almacén', 'whseid', etc.
                    if valor_str and valor_str != '' and valor_str != 'nan' and valor_str not in ['almacen', 'whseid', 'warehouse', 'almacén']:
                        # Verificar que tenga formato de WHSEID (empieza con wmwhse)
                        if valor_str.startswith('wmwhse'):
                            valores_whseid.append(valor_str)
                            print(f"   ✅ Fila {i}: Valor válido encontrado: {valor_str}")
            
            print(f"\n🔍 Valores encontrados en columna GS: {valores_whseid[:10]}")  # Mostrar primeros 10
            
            if len(valores_whseid) == 0:
                print("⚠️ No se encontraron valores válidos en la columna GS")
                return None
            
            # Obtener el primer valor no vacío y no nulo
            whseid_raw = valores_whseid[0]
            print(f"🏷️ WHSEID encontrado: '{whseid_raw}'")
            
            # Buscar en el mapeo
            sede = self.MAPEO_SEDES.get(whseid_raw)
            
            if sede:
                print(f"✅ Sede determinada: {sede} (WHSEID: {whseid_raw})")
            else:
                print(f"⚠️ WHSEID '{whseid_raw}' no está en el mapeo de sedes")
                # Si no está en el mapeo, usar el valor como está (capitalizado)
                sede = whseid_raw.upper()
                print(f"   Usando valor original: {sede}")
            
            return sede
            
        except Exception as e:
            print(f"❌ Error extrayendo sede: {str(e)}")
            self.logger.error(f"Error extrayendo sede: {e}", exc_info=True)
            return None
    
    def _aplicar_filtros_fecha(self, df, col_fecha, fecha_desde, fecha_hasta):
        """Aplica filtros de fecha al dataframe"""
        stats = {
            'filas_filtradas_fecha': 0,
            'fecha_min': None,
            'fecha_max': None
        }
        
        if col_fecha not in df.columns or len(df) == 0:
            return df, stats
        
        print(f"🔍 ANTES DEL FILTRO - Total filas: {len(df)}")
        print(f"🔍 Fechas recibidas - Desde: '{fecha_desde}', Hasta: '{fecha_hasta}'")
        
        # Convertir columna a datetime con dayfirst=True
        try:
            df[col_fecha] = pd.to_datetime(df[col_fecha], errors='coerce', dayfirst=True)
            print(f"✅ Columna convertida a datetime")
        except Exception as e:
            print(f"❌ Error convirtiendo columna: {e}")
            return df, stats
        
        # Mostrar algunas fechas antes del filtro
        fechas_muestra = df[col_fecha].dropna().head(5)
        print(f"🔍 Muestra de fechas en archivo: {list(fechas_muestra)}")
        
        # Guardar estadísticas de fechas originales
        fechas_validas = df[df[col_fecha].notna()]
        if len(fechas_validas) > 0:
            stats['fecha_min'] = fechas_validas[col_fecha].min().strftime('%Y-%m-%d')
            stats['fecha_max'] = fechas_validas[col_fecha].max().strftime('%Y-%m-%d')
            print(f"🔍 Rango original en archivo: {stats['fecha_min']} - {stats['fecha_max']}")
        
        # Aplicar filtros
        df_filtrado = df.copy()
        original_len = len(df_filtrado)
        
        # FILTRO DESDE
        if fecha_desde and fecha_desde != '' and fecha_desde != 'null':
            try:
                fecha_desde_dt = pd.to_datetime(fecha_desde, format='%Y-%m-%d')
                print(f"🔍 Aplicando filtro DESDE: {fecha_desde_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] >= fecha_desde_dt]
                print(f"   Filas después de filtrar DESDE: {len(df_filtrado)}")
            except Exception as e:
                print(f"❌ Error con fecha_desde '{fecha_desde}': {e}")
        
        # FILTRO HASTA
        if fecha_hasta and fecha_hasta != '' and fecha_hasta != 'null':
            try:
                fecha_hasta_dt = pd.to_datetime(fecha_hasta, format='%Y-%m-%d') + pd.Timedelta(days=1)
                print(f"🔍 Aplicando filtro HASTA: {fecha_hasta_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] <= fecha_hasta_dt]
                print(f"   Filas después de filtrar HASTA: {len(df_filtrado)}")
            except Exception as e:
                print(f"❌ Error con fecha_hasta '{fecha_hasta}': {e}")
        
        stats['filas_filtradas_fecha'] = original_len - len(df_filtrado)
        print(f"🔍 Total filas filtradas por fecha: {stats['filas_filtradas_fecha']}")
        print(f"🔍 Filas después del filtro: {len(df_filtrado)}")
        
        # Convertir fechas a string para JSON
        df_filtrado[col_fecha] = df_filtrado[col_fecha].apply(
            lambda x: x.strftime('%Y-%m-%d') if pd.notna(x) else ''
        )
        
        return df_filtrado, stats
    
    def _convertir_dataframe_a_lista(self, df):
        """Convierte DataFrame a lista de listas"""
        if df.empty:
            return []
        
        if 'ADDDATE' in df.columns:
            df['ADDDATE'] = df['ADDDATE'].apply(
                lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
            )
        
        return df.fillna('').values.tolist()
    
    def _encontrar_hoja_detail(self, excel_file):
        """Busca la hoja que contiene 'Detail'"""
        sheet_names = excel_file.sheet_names
        
        for name in sheet_names:
            if 'Detail' in name or 'detail' in name:
                return name
        
        return sheet_names[0] if sheet_names else 'Sheet1'
    
    def procesar(self, archivo_path, fecha_desde=None, fecha_hasta=None, request_id=None):
        """Procesa el archivo de despacho"""
        self.logger.info(f"[{request_id}] Procesando archivo de despacho: {archivo_path}")
        print(f"\n{'='*60}")
        print(f"[{request_id}] PROCESANDO DESPACHO")
        print(f"[{request_id}] Archivo: {os.path.basename(archivo_path)}")
        print(f"[{request_id}] Fechas recibidas - Desde: '{fecha_desde}', Hasta: '{fecha_hasta}'")
        print(f"{'='*60}\n")
        
        # ============================================
        # EXTRAER SEDE DESDE HOJA "Data" COLUMNA GS
        # ============================================
        sede_determinada = self._extraer_sede_desde_hoja_data(archivo_path)
        
        if sede_determinada:
            print(f"🏢 SEDE DETERMINADA: {sede_determinada}")
        else:
            print(f"⚠️ No se pudo determinar la sede, se guardará como 'No especificada'")
            sede_determinada = 'No especificada'
        
        try:
            # Leer Excel
            excel_file = pd.ExcelFile(archivo_path, engine='openpyxl')
            hoja = self._encontrar_hoja_detail(excel_file)
            
            df = pd.read_excel(archivo_path, sheet_name=hoja, engine='openpyxl', header=None)
            self.logger.info(f"[{request_id}] Hoja: {hoja}, Filas totales: {len(df)}")
            print(f"📊 Hoja Detail encontrada: {hoja}, Filas totales: {len(df)}")
            
            # Extraer columnas específicas
            df_resultado = pd.DataFrame()
            for nombre, info in self.COLUMNAS.items():
                idx = self._excel_col_to_index(info['col'])
                if idx < len(df.columns) and len(df) > 1:
                    df_resultado[nombre] = df.iloc[1:, idx].reset_index(drop=True)
            
            # Limpiar filas vacías
            df_resultado = df_resultado.dropna(subset=['ORDERKEY', 'SKU'], how='all')
            print(f"📊 Después de limpiar vacíos: {len(df_resultado)} filas")
            
            # Filtrar por STATUS
            if 'STATUS' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['STATUS_STR'] = df_resultado['STATUS'].astype(str).str.strip()
                df_resultado = df_resultado[df_resultado['STATUS_STR'].isin(self.STATUS_VALIDOS)].copy()
                print(f"📊 Después de filtrar STATUS: {len(df_resultado)} filas")
            
            # Procesar cantidades
            if 'SHIPPEDQTY' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['QTY_ENTERO'] = df_resultado['SHIPPEDQTY'].apply(self._extraer_parte_entera)
            
            # APLICAR FILTROS DE FECHA
            df_resultado, stats_fecha = self._aplicar_filtros_fecha(
                df_resultado, 'ADDDATE', fecha_desde, fecha_hasta
            )
            print(f"📊 Después de filtrar fechas: {len(df_resultado)} filas")
            
            # Agrupar por ORDERKEY + SKU
            if len(df_resultado) > 0:
                df_resultado['GRUPO'] = df_resultado['ORDERKEY'].astype(str) + '_' + df_resultado['SKU'].astype(str)
                
                def agregar_grupo(g):
                    if len(g) == 0:
                        return None
                    primero = g.iloc[0]
                    uom = str(primero['UOM']).strip().upper() if pd.notna(primero['UOM']) else ''
                    cantidad = int(g['QTY_ENTERO'].sum()) if 'QTY_ENTERO' in g.columns else 0
                    
                    return pd.Series({
                        'ORDERKEY': primero['ORDERKEY'],
                        'SKU': primero['SKU'],
                        'STORERKEY': primero['STORERKEY'] if 'STORERKEY' in g.columns else '',
                        'EXTERNORDERKEY': primero['EXTERNORDERKEY'] if 'EXTERNORDERKEY' in g.columns else '',
                        'UNIDADES': cantidad if uom == 'UN' else 0,
                        'CAJAS': cantidad if uom == 'CJ' else 0,
                        'PALLETS': cantidad if uom == 'PL' else 0,
                        'STATUS': primero['STATUS'] if 'STATUS' in g.columns else '',
                        'ADDDATE': g['ADDDATE'].iloc[0] if 'ADDDATE' in g.columns else '',
                    })
                
                df_agrupado = df_resultado.groupby('GRUPO', group_keys=False).apply(agregar_grupo).reset_index(drop=True)
                print(f"📊 Después de agrupar: {len(df_agrupado)} filas")
            else:
                df_agrupado = pd.DataFrame(columns=self.HEADERS)
            
            # Asegurar que las fechas estén en formato string
            if 'ADDDATE' in df_agrupado.columns and len(df_agrupado) > 0:
                df_agrupado['ADDDATE'] = df_agrupado['ADDDATE'].apply(
                    lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
                )
            
            # Calcular estadísticas
            stats = {
                'total_filas': len(df_agrupado),
                'filas_filtradas_fecha': stats_fecha['filas_filtradas_fecha'],
                'fecha_min': stats_fecha['fecha_min'],
                'fecha_max': stats_fecha['fecha_max'],
                'hoja_procesada': hoja,
                'total_unidades': int(df_agrupado['UNIDADES'].sum()) if len(df_agrupado) > 0 else 0,
                'total_cajas': int(df_agrupado['CAJAS'].sum()) if len(df_agrupado) > 0 else 0,
                'total_pallets': int(df_agrupado['PALLETS'].sum()) if len(df_agrupado) > 0 else 0,
                'orderkeys_unicos': df_agrupado['ORDERKEY'].nunique() if len(df_agrupado) > 0 else 0,
                'sede': sede_determinada
            }
            
            preview_data = self._convertir_dataframe_a_lista(df_agrupado.head(100))
            full_data = self._convertir_dataframe_a_lista(df_agrupado)
            
            # Obtener el WHSEID original para debug
            whseid_original = None
            try:
                excel_file_debug = pd.ExcelFile(archivo_path, engine='openpyxl')
                if 'Data' in [n.lower() for n in excel_file_debug.sheet_names]:
                    df_data_debug = pd.read_excel(archivo_path, sheet_name='Data', engine='openpyxl', header=None)
                    col_gs_idx = self._excel_col_to_index('GS')
                    if col_gs_idx < len(df_data_debug.columns):
                        for i in range(3, min(10, len(df_data_debug))):
                            val = df_data_debug.iloc[i, col_gs_idx]
                            if pd.notna(val):
                                val_str = str(val).strip().lower()
                                if val_str.startswith('wmwhse'):
                                    whseid_original = val_str
                                    break
            except:
                pass
            
            print(f"\n📊 RESUMEN FINAL:")
            print(f"   Total registros: {len(df_agrupado)}")
            print(f"   Unidades: {stats['total_unidades']}")
            print(f"   Cajas: {stats['total_cajas']}")
            print(f"   Pallets: {stats['total_pallets']}")
            print(f"   Rango fechas: {stats['fecha_min']} - {stats['fecha_max']}")
            print(f"   🏢 SEDE DETECTADA: {stats['sede']}")
            print(f"   🏷️ WHSEID ORIGINAL: {whseid_original}")
            print(f"{'='*60}\n")
            
            return {
                'success': True,
                'total_registros': len(df_agrupado),
                'headers': self.HEADERS,
                'data': preview_data,
                'data_completa': full_data,
                'stats': stats,
                'sede': sede_determinada,
                'whseid': whseid_original,
                'metadata': {
                    'archivo_nombre': os.path.basename(archivo_path),
                    'hoja_procesada': hoja,
                    'fechas_aplicadas': {
                        'desde': fecha_desde,
                        'hasta': fecha_hasta
                    },
                    'sede_detectada': sede_determinada,
                    'whseid_original': whseid_original
                }
            }
            
        except Exception as e:
            self.logger.error(f"[{request_id}] Error procesando: {str(e)}", exc_info=True)
            raise