# Field Listing

## Call
| Element | Type |
| --- | --- |
| FireControlledTime | xs:string |
| CallNumber | xs:int |
| CallId | xs:int |
| CallSource | xs:string |
| Location | Location |
| CallerName | xs:string |
| CallerPhone | xs:long |
| CreateDateTime | xs:string |
| CloseDateTime | xs:string |
| CreatedBy | xs:string |
| Incidents | ArrayOfIncident |
| Narratives | ArrayOfNarrative |
| AssignedUnits | ArrayOfUnit |
| ClosedFlag | xs:boolean |
| CanceledFlag | xs:boolean |
| AgencyContexts | ArrayOfAgencyContext |
| Dispositions | ArrayOfCallDisposition |
| Persons | ArrayOfPerson |
| Vehicles | ArrayOfVehicle |
| NatureOfCall | xs:string |
| AlarmLevel | xs:unsignedByte |

## Location
| Element | Type |
| --- | --- |
| CommonName | xs:string |
| FullAddress | xs:string |
| Qualifier | xs:string |
| City | xs:string |
| Venue | xs:string |
| PoliceBeat | xs:string |
| FireQuadrant | xs:string |
| EmsDistrict | xs:string | 
| PoliceOri | xs:string |
| FireOri | xs:string | 
| EmsOri | xs:string |
| NearestCrossStreets | xs:string |
| LatitudeY | xs:double |
| LongitudeX | xs:double | 
| RuralGrid | xs:string |
| HouseNumber | xs:int | 
| AdditionalInfo | xs:string |
| PrefixDirectional | xs:string | 
| PrefixType | xs:string |
| StreetName | xs:string | 
| StreetType | xs:string |
| StreetDirectional | xs:string | 
| XPrefixDirectional | xs:string |
| XPrefixType | xs:string | 
| XStreetName | xs:string |
| XStreetType | xs:string |
| XStreetDirectional | xs:string |
| State | xs:string | 
| Zip | xs:string |
| Zip4 | xs:string | 
| StationArea | xs:string |
| CustomLayer | xs:string | 
| CensusTract | xs:string |

## Incident
| Element | Type |
| --- | --- |
| Number | xs:string |
| Jurisdiction | xs:string | 
| CreateDateTime | xs:string |
| Type | xs:string | 
| TypeDescription | xs:string |
| AgencyType | xs:string |
| NarrativeElement | Type |
| Text | xs:string |
| CreateDateTime xs:string |
| CreateUser xs:string |
| Type xs:string |

## Unit
| Element | Type |
| --- | --- |
| UnitNumber | xs:string |
| IsPrimary | xs:boolean |
| UnitLogs | ArrayOfUnitLog |
| Dispositions | ArrayOfUnitDisposition | 
| Jurisdiction | xs:string |
| Personnel | ArrayOfUnitPersonnel | 
| DispatchDateTime | xs:string |
| ArriveDateTime | xs:string |
| EnrouteDateTime | xs:string |
| ClearDateTime | xs:string | 
| AtPatientDateTime | xs:string |
| StagedDateTime | xs:string |
| TransportDateTime | xs:string |
| AtHospitalDateTime | xs:string |
| DepartHospitalDateTime | xs:string |
| Type | xs:string |

## UnitLog
| Element | Type |
| --- | --- |
| Status | xs:string |
| DateTime | xs:string |

## UnitDisposition
| Element | Type |
| --- | --- |
| Name | xs:string |
| Description | xs:string | 
| DateTime | xs:string |

## UnitPersonnel
| Element | Type |
| --- | --- |
| IDNumber | xs:string |
| ShieldNumber | xs:string |
| Jurisdiction | xs:string |
| LastName | xs:string | 
| FirstName | xs:string |
| MiddleName | xs:string |
| IsPrimaryOfficer | xs:boolean |

## AgencyContext
| Element | Type |
| --- | --- |
| AgencyType | xs:string |
| CallType | xs:string |
| CreatedDateTime | xs:string |
| ClosedDateTime | xs:string | 
| CanceledFlag | xs:boolean |
| ClosedFlag | xs:boolean | 
| Priority | xs:string |
| RadioChannel | xs:string | 
| Status | xs:string |
| EmdCode | xs:string |
| Dispatcher | xs:string |

## CallDisposition
| Element | Type |
| --- | --- |
| Name | xs:string |
| Description | xs:string |
| Count | xs:int |

## Person 
| Element | Type |
| --- | --- |
| Sex | xs:string |
| Race | xs:string |
| LicenseNumber | xs:string |
| LicenseState | xs:string |
| EyeColor | xs:string |
| HairColor | xs:string |
| SSN | xs:int |
| DateOfBirth | xs:string |
| LastName | xs:string |
| FirstName | xs:string |
| MiddleName | xs:string |
| NameSuffix | xs:string |
| ContactPhone | xs:long |
| Role | xs:string |
| PrimaryCallerFlag | xs:boolean |
| GlobalSubjectId | xs:int |
| HeightInches | xs:int |
| Weight | xs:decimal |
| Address | Location |

## Vehicle 
| Element | Type |
| --- | --- |
| GlobalVehicleId | xs:int |
| LicenseNumber | xs:string |
| LicenseState | xs:string |
| VIN | xs:string  |
| Role | xs:string |
| Type | xs:string | 
| Make | xs:string |
| Model | xs:string |
| Description | xs:string |
| Color | xs:string | 
| Year | xs:int | 
